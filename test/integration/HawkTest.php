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
     * Test correct request passes validation.
     */
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
     * Test illegal algorithm returns not allowed message.
     */
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

        $this->assertSame('Algorithm not allowed.', $out);
    }

    /**
     * Test correct request without payload validation passes.
     */
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

        $this->assertSame('Hello world!', $out);
    }

    /**
     * Test wrong user credentials denied.
     */
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

        $this->assertSame('Request not authentic.', $out);
    }

    /**
     * Test payload mismatch denied.
     */
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
        $header = 'Hawk id="user", ts="'.$ts.'", nonce="'.$nonce.'", mac="'.$mac.'", hash="", alg="'.$alg.'"';

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

    /**
     * Test expired request denied.
     */
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

        $this->assertSame('Request expired.', $out);
    }

    /**
     * Test future request denied.
     */
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

        $this->assertSame('Request too far into future.', $out);
    }

    /**
     * Test no user given.
     */
    public function testMissingUserCredentials()
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
        $header = 'Hawk ts="'.$ts.'", nonce="'.$nonce.'", mac="'.$mac.'", alg="'.$alg.'"';

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

    /**
     * Test duplicate nonce in file denied.
     */
    public function testFileDuplicateNonce()
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
        $header = 'Hawk id="user", ts="'.$ts.'", nonce="'.$nonce.'", mac="'.$mac.'", alg="'.$alg.'"';

        $this->fakeRequest($header);

        // Set up application
        $app = new Freischutz\Application\Core($this->configFile);
        // Force reading URI from $_SERVER['REQUEST_URI']
        $app->router->setUriSource(Router::URI_SOURCE_SERVER_REQUEST_URI);

        // Catch output from successful request
        ob_start();
        $resultOk = $app->run();
        $outOk = ob_get_contents();
        ob_end_clean();

        // Change some details
        $ts = date('U');
        $method = 'POST';

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
        $header = 'Hawk id="user", ts="'.$ts.'", nonce="'.$nonce.'", mac="'.$mac.'", alg="'.$alg.'"';

        $this->fakeRequest($header);

        // Catch output
        ob_start();
        $resultDeny = $app->run();
        $outDeny = ob_get_contents();
        ob_end_clean();

        $this->assertSame('Hello world!', $outOk);
        $this->assertSame('Duplicate nonce.', $outDeny);
    }
}
