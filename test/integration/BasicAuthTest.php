<?php

use Phalcon\Config\Adapter\Ini as Config;
use Phalcon\Mvc\Router;
use PHPUnit\Framework\TestCase;

class BasicAuthTest extends TestCase
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
     * Test correct request passes validation.
     * @preserveGlobalState disabled
     * @runInSeparateProcess
     */
    public function testOk()
    {
        $auth = base64_encode('userb:pw');
        $header = "Basic $auth";

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
     * Test wrong key denied.
     */
    public function testWrongKey()
    {
        $auth = base64_encode('userb:wp');
        $header = "Basic $auth";

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
        ob_get_clean();

        $this->assertSame('Password did not verify.', $out);
    }

    /**
     * Test invalid user given.
     */
    public function testMissingUserCredentials()
    {
        $auth = base64_encode('wrong:pw');
        $header = "Basic $auth";

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
        ob_get_clean();

        $this->assertSame('User does not exist.', $out);
    }

    /**
     * Test no user given.
     */
    public function testNoUserGiven()
    {
        $auth = base64_encode(':pw');
        $header = "Basic $auth";

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
        ob_get_clean();

        $this->assertSame('No user provided in request.', $out);
    }
}
