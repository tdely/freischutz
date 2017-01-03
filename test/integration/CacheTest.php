<?php

use Phalcon\Config\Adapter\Ini as Config;
use Phalcon\Mvc\Router;
use PHPUnit\Framework\TestCase;

class CacheTest extends TestCase
{
    private $configFile;

    public static function setUpBeforeClass()
    {
        unlink('_freischutz_acl');
        unlink('_freischutz_routes');
        unlink('_freischutz_users');
        unlink('_freischutz_nonce_PyMA5I');
    }

    public function setUp()
    {
        $appDir = __DIR__ . '/_shared';
        $libDir = __DIR__ . '/../../lib';
        $this->configFile = new Config($appDir . '/../_config/config_cache.ini');
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
     * Test uncached OK
     */
    public function testInitializeCacheOk()
    {
        $alg = 'sha256';
        // Create payload hash
        $hash = hash($alg, 'hawk.1.payload\ntext/plain\n\n');

        $ts = date('U');
        $nonce = 'PyMA5I';
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
        $header = 'Hawk id="user", ts="'.$ts.'", nonce="'.$nonce.'", mac="'.$mac.'", hash="'.$hash.'", alg="'.$alg.'"';

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

    /**
     * Test cached works OK
     */
    public function testReadFromCacheOk()
    {
        $alg = 'sha256';
        // Create payload hash
        $hash = hash($alg, 'hawk.1.payload\ntext/plain\n\n');

        $ts = date('U');
        $nonce = 'PyMA5I';
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
        $header = 'Hawk id="user", ts="'.$ts.'", nonce="'.$nonce.'", mac="'.$mac.'", hash="'.$hash.'", alg="'.$alg.'"';

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

        $this->assertFileExists('_freischutz_acl');
        $this->assertFileExists('_freischutz_routes');
        $this->assertFileExists('_freischutz_users');
        $this->assertFileExists('_freischutz_nonce_PyMA5I');
        $this->assertSame('Duplicate nonce.', $out);
    }
}
