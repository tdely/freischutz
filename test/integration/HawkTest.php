<?php

use Phalcon\Config\Adapter\Ini as Config;
use Phalcon\Mvc\Router;
use PHPUnit\Framework\TestCase;

class HawkTest extends TestCase
{
    private $configFile;
    private $configDb;

    public function setUp()
    {
        $appDir = __DIR__ . '/_shared';
        $libDir = __DIR__ . '/../../lib';
        $this->configFile = new Config($appDir . '/../_config/config_hawk.ini');
        $this->configFile->application->offsetSet('app_dir', $appDir);
        require $appDir . '/config/autoloader.php';

    }

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

    public function testOk()
    {
        $alg = 'sha256';
        // Create payload hash
        $hash = hash($alg, 'hawk.1.payload\ntext/plain\n\n');

        $ts = date('U');
        $nonce = bin2hex(openssl_random_pseudo_bytes(3));
        $method = 'GET';
        $uri = '/hello';
        $host = 'localhost';
        $port = 80;

        // Create request string
        $message = 'hawk.1.header\n' .
                   $ts . '\n' .
                   $nonce . '\n' .
                   $method . '\n' .
                   $uri . '\n' .
                   $host . '\n' .
                   $port . '\n' .
                   $hash . '\n' .
                   '' . '\n';

        $mac = base64_encode(hash_hmac($alg, $message, 'pw', true));
        $header = 'Hawk, id="user", ts="'.$ts.'", nonce="'.$nonce.'", mac="'.$mac.'", hash="'.$hash.'", alg="'.$alg.'"';

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

        $this->assertSame('Hello world!', $out);
    }

    public function testIllegalAlgorithm()
    {
        $alg = 'md5';
        // Create payload hash
        $hash = hash($alg, 'hawk.1.payload\ntext/plain\n\n');

        $ts = date('U');
        $nonce = bin2hex(openssl_random_pseudo_bytes(3));
        $method = 'GET';
        $uri = '/hello';
        $host = 'localhost';
        $port = 80;

        // Create request string
        $message = 'hawk.1.header\n' .
                   $ts . '\n' .
                   $nonce . '\n' .
                   $method . '\n' .
                   $uri . '\n' .
                   $host . '\n' .
                   $port . '\n' .
                   $hash . '\n' .
                   '' . '\n';

        $mac = base64_encode(hash_hmac($alg, $message, 'pw', true));
        $header = 'Hawk, id="user", ts="'.$ts.'", nonce="'.$nonce.'", mac="'.$mac.'", hash="'.$hash.'", alg="'.$alg.'"';

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

        $this->assertSame('Algorithm not allowed.', $out);
    }

    public function testNoPayloadValidationOk()
    {
        $alg = 'sha256';

        $ts = date('U');
        $nonce = bin2hex(openssl_random_pseudo_bytes(3));
        $method = 'GET';
        $uri = '/hello';
        $host = 'localhost';
        $port = 80;

        // Create request string
        $message = 'hawk.1.header\n' .
                   $ts . '\n' .
                   $nonce . '\n' .
                   $method . '\n' .
                   $uri . '\n' .
                   $host . '\n' .
                   $port . '\n' .
                   '' . '\n' .
                   '' . '\n';

        $mac = base64_encode(hash_hmac($alg, $message, 'pw', true));
        $header = 'Hawk, id="user", ts="'.$ts.'", nonce="'.$nonce.'", mac="'.$mac.'", alg="'.$alg.'"';

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

        $this->assertSame('Hello world!', $out);
    }

    public function testWrongUserCredentials()
    {
        $alg = 'sha256';

        $ts = date('U');
        $nonce = bin2hex(openssl_random_pseudo_bytes(3));
        $method = 'GET';
        $uri = '/hello';
        $host = 'localhost';
        $port = 80;

        // Create request string
        $message = 'hawk.1.header\n' .
                   $ts . '\n' .
                   $nonce . '\n' .
                   $method . '\n' .
                   $uri . '\n' .
                   $host . '\n' .
                   $port . '\n' .
                   '' . '\n' .
                   '' . '\n';

        $mac = base64_encode(hash_hmac($alg, $message, 'wp', true));
        $header = 'Hawk, id="user", ts="'.$ts.'", nonce="'.$nonce.'", mac="'.$mac.'", alg="'.$alg.'"';

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

        $this->assertSame('Request not authentic.', $out);
    }

    public function testPayloadMismatch()
    {
        $alg = 'sha256';
        // Create payload hash
        $hash = hash($alg, 'hawk.1.payload\ntext/plain\n\n');

        $ts = date('U');
        $nonce = bin2hex(openssl_random_pseudo_bytes(3));
        $method = 'GET';
        $uri = '/hello';
        $host = 'localhost';
        $port = 80;

        // Create request string
        $message = 'hawk.1.header\n' .
                   $ts . '\n' .
                   $nonce . '\n' .
                   $method . '\n' .
                   $uri . '\n' .
                   $host . '\n' .
                   $port . '\n' .
                   $hash . '\n' .
                   '' . '\n';

        $mac = base64_encode(hash_hmac($alg, $message, 'pw', true));
        $header = 'Hawk, id="user", ts="'.$ts.'", nonce="'.$nonce.'", mac="'.$mac.'", hash="", alg="'.$alg.'"';

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

        $this->assertSame('Payload mismatch.', $out);
    }

    public function testExpiredRequest()
    {
        $alg = 'sha256';
        // Create payload hash
        $hash = hash($alg, 'hawk.1.payload\ntext/plain\n\n');

        $ts = date('U') - 70;
        $nonce = bin2hex(openssl_random_pseudo_bytes(3));
        $method = 'GET';
        $uri = '/hello';
        $host = 'localhost';
        $port = 80;

        // Create request string
        $message = 'hawk.1.header\n' .
                   $ts . '\n' .
                   $nonce . '\n' .
                   $method . '\n' .
                   $uri . '\n' .
                   $host . '\n' .
                   $port . '\n' .
                   $hash . '\n' .
                   '' . '\n';

        $mac = base64_encode(hash_hmac($alg, $message, 'pw', true));
        $header = 'Hawk, id="user", ts="'.$ts.'", nonce="'.$nonce.'", mac="'.$mac.'", hash="'.$hash.'", alg="'.$alg.'"';

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

        $this->assertSame('Request expired.', $out);
    }

    public function testFutureRequest()
    {
        $alg = 'sha256';
        // Create payload hash
        $hash = hash($alg, 'hawk.1.payload\ntext/plain\n\n');

        $ts = date('U') + 70;
        $nonce = bin2hex(openssl_random_pseudo_bytes(3));
        $method = 'GET';
        $uri = '/hello';
        $host = 'localhost';
        $port = 80;

        // Create request string
        $message = 'hawk.1.header\n' .
                   $ts . '\n' .
                   $nonce . '\n' .
                   $method . '\n' .
                   $uri . '\n' .
                   $host . '\n' .
                   $port . '\n' .
                   $hash . '\n' .
                   '' . '\n';

        $mac = base64_encode(hash_hmac($alg, $message, 'pw', true));
        $header = 'Hawk, id="user", ts="'.$ts.'", nonce="'.$nonce.'", mac="'.$mac.'", hash="'.$hash.'", alg="'.$alg.'"';

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

        $this->assertSame('Request too far into future.', $out);
    }
}
