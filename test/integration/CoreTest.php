<?php

use Phalcon\Config\Adapter\Ini as Config;
use Phalcon\Mvc\Router;
use PHPUnit\Framework\TestCase;

class CoreTest extends TestCase
{
    private $configFile;
    private $configDb;

    public function setUp()
    {
        $appDir = __DIR__ . '/_shared';
        $libDir = __DIR__ . '/../../lib';
        $this->configFile = new Config($appDir . '/../_shared/config/config.ini');
        $this->configFile->application->offsetSet('app_dir', $appDir);
        $this->configFile->application->offsetSet('authenticate', null);
        $this->configFile->acl->offsetSet('enable', false);
        require $appDir . '/config/autoloader.php';

    }

    /**
     * Fake some request stuff.
     */
    public function fakeRequest()
    {
        $_SERVER['CONTENT_TYPE'] = 'text/plain';
        $_SERVER['REQUEST_URI'] = '/hello';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['SERVER_NAME'] = 'localhost';
        $_SERVER['SERVER_PORT'] = 80;
    }

    /**
     * Test the simple 'Hello world!' action.
     */
    public function testCoreOk()
    {
        $this->fakeRequest();

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
     * Test that the base_uri option works by setting URI to just '/hello'
     * and use base_uri to prefix '/test/'.
     */
    public function testCoreBaseUriOk()
    {
        $this->fakeRequest();
        $_SERVER['REQUEST_URI'] = '/hello';

        $config = $this->configFile;
        $config->application->offsetSet('base_uri', '/test');
        // Set up application
        $app = new Freischutz\Application\Core($config);
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
    * Test that request without matching routes return missing resource message
    * correctly.
    */
    public function testCoreMissingRoute()
    {
        $this->fakeRequest();
        $_SERVER['REQUEST_URI'] = '/fail';

        $config = $this->configFile;
        // Set up application
        $app = new Freischutz\Application\Core($config);
        // Force reading URI from $_SERVER['REQUEST_URI']
        $app->router->setUriSource(Router::URI_SOURCE_SERVER_REQUEST_URI);

        // Catch output
        ob_start();
        $result = $app->run();
        $out = ob_get_contents();
        ob_end_clean();

        $this->assertSame('Resource not found.', $out);
    }
}
