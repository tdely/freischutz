<?php

use Phalcon\Config\Adapter\Ini as Config;
use Phalcon\Mvc\Router;
use PHPUnit\Framework\TestCase;

class AclTest extends TestCase
{
    private $configFile;
    private $configDb;

    public function setUp()
    {
        $appDir = __DIR__ . '/_shared';
        $libDir = __DIR__ . '/../../lib';
        $this->configFile = new Config($appDir . '/../_shared/config/config.ini');
        $this->configFile->application->offsetSet('app_dir', $appDir);
        require $appDir . '/config/autoloader.php';

    }

    /**
     * Fake some request stuff.
     */
    public function fakeRequest($header)
    {
        // Fake some request stuff
        $_SERVER['HTTP_AUTHORIZATION'] = $header;
        $_SERVER['CONTENT_TYPE'] = 'text/plain';
        $_SERVER['REQUEST_URI'] = '/hello';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['SERVER_NAME'] = 'localhost';
        $_SERVER['SERVER_PORT'] = 80;
    }

    /**
     * Test that ACL allows user access to a resource.
     */
    public function testAclOk()
    {
        $alg = 'sha256';

        $ts = date('U');
        $nonce = bin2hex(openssl_random_pseudo_bytes(3));
        $method = 'GET';
        $uri = '/hello';
        $host = 'localhost';
        $port = 80;

        // Create request string
        $message = "hawk.1.header\n" .
                   $ts . "\n" .
                   $nonce . "\n" .
                   $method . "\n" .
                   $uri . "\n" .
                   $host . "\n" .
                   $port . "\n" .
                   "\n" .
                   "\n";

        $mac = base64_encode(hash_hmac($alg, $message, 'pw', true));
        $header = 'Hawk id="user", ts="'.$ts.'", nonce="'.$nonce.'", mac="'.$mac.'", alg="'.$alg.'"';

        $this->fakeRequest($header);

        // Set up application
        $app = new Freischutz\Application\Core($this->configFile);
        // Force reading URI from $_SERVER['REQUEST_URI']
        $app->router->setUriSource(Router::URI_SOURCE_SERVER_REQUEST_URI);

        // Catch output
        ob_start();
        $result = $app->run();
        $out = ob_get_contents();
        ob_end_clean();

        $this->assertTrue($out === 'Hello world!', $out);
    }

    /**
     * Test that ACL denies user access to a resource.
     */
    public function testAclNoAccess()
    {
        $alg = 'sha256';

        $ts = date('U');
        $nonce = bin2hex(openssl_random_pseudo_bytes(3));
        $method = 'GET';
        $uri = '/hello';
        $host = 'localhost';
        $port = 80;

        // Create request string
        $message = "hawk.1.header\n" .
                   $ts . "\n" .
                   $nonce . "\n" .
                   $method . "\n" .
                   $uri . "\n" .
                   $host . "\n" .
                   $port . "\n" .
                   "\n" .
                   "\n";

        $mac = base64_encode(hash_hmac($alg, $message, 'pass', true));
        $header = 'Hawk id="bob", ts="'.$ts.'", nonce="'.$nonce.'", mac="'.$mac.'", alg="'.$alg.'"';

        $this->fakeRequest($header);

        // Set up application
        $app = new Freischutz\Application\Core($this->configFile);
        // Force reading URI from $_SERVER['REQUEST_URI']
        $app->router->setUriSource(Router::URI_SOURCE_SERVER_REQUEST_URI);

        // Catch output
        ob_start();
        $result = $app->run();
        $out = ob_get_contents();
        ob_end_clean();

        $this->assertTrue($out === 'Access denied.', $out);
    }

    /**
     * Test that ACL doesn't interfere with missing resource action.
     */
    public function testAclMissingResourceOk()
    {
        $alg = 'sha256';

        $ts = date('U');
        $nonce = bin2hex(openssl_random_pseudo_bytes(3));
        $method = 'GET';
        $uri = '/goodbye';
        $host = 'localhost';
        $port = 80;

        // Create request string
        $message = "hawk.1.header\n" .
                   $ts . "\n" .
                   $nonce . "\n" .
                   $method . "\n" .
                   $uri . "\n" .
                   $host . "\n" .
                   $port . "\n" .
                   "\n" .
                   "\n";

        $mac = base64_encode(hash_hmac($alg, $message, 'pw', true));
        $header = 'Hawk id="user", ts="'.$ts.'", nonce="'.$nonce.'", mac="'.$mac.'", alg="'.$alg.'"';

        $this->fakeRequest($header);
        $_SERVER['REQUEST_URI'] = '/goodbye';

        // Set up application
        $app = new Freischutz\Application\Core($this->configFile);
        // Force reading URI from $_SERVER['REQUEST_URI']
        $app->router->setUriSource(Router::URI_SOURCE_SERVER_REQUEST_URI);

        // Catch output
        ob_start();
        $result = $app->run();
        $out = ob_get_contents();
        ob_end_clean();

        $this->assertTrue($out === 'Resource not found.', $out);
    }
}
