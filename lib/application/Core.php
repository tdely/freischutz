<?php
namespace Freischutz\Application;

use Phalcon\DI\FactoryDefault;
use Phalcon\Mvc\Application;
use Phalcon\Mvc\Dispatcher;
use Phalcon\Mvc\View;
use Phalcon\Mvc\Router;
use Phalcon\Mvc\Router\Group;

/**
 * Freischutz\Application\Core
 */
class Core extends Application
{

    /**
     * Set dispatcher
     *
     * @param \Phalcon\DI $di Dependency Injector
     */
    private function setDispatcher($di)
    {
        $dispatcher = new Dispatcher();
        $namespace = $this->config->application->get(
            'controller_namespace',
            'Freischutz\Controllers'
        );
        $dispatcher->setDefaultNamespace($namespace);

        $di->set('dispatcher', $dispatcher);
    }

    /**
     * Set routes
     *
     * @throws \Exception on no routes or malformed route definition rows
     * @param \Phalcon\DI $di Dependency Injector
     */
    private function setRoutes($di)
    {
        $routesDir = $this->config->application->app_dir .
            $this->config->application->routes_dir;

        // Group routes for simplicity
        $group = new Group();
        $group->setPrefix($this->config->application->base_uri);

        $routes = array();

        /**
         * Load routes
         */
        foreach (glob($routesDir . "/*Routes.php") as $file) {
            $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                // Allow comments starting with '#', ';', or '//'
                if ($line[0] === '#' || $line[0] === ';'
                        || substr($line, 0, 2) === '//') {
                    continue;
                }
                $parts = explode(',', $line);
                if (sizeof($parts) !== 4) {
                    throw new \Exception("Malformed row in $file: $line");
                }
                $group->add(
                    $parts[2],
                    array(
                        'controller' => $parts[0],
                        'action' => $parts[1],
                    ),
                    strtoupper($parts[3])
                );
            };
        }

        if (!isset($routes)) {
            throw new \Exception("No routes found in $routesDir");
        }

        /**
         * Set up router
         */
        $router = new Router(false);
        $router->mount($group);

        // Remove trailing slashes
        $router->removeExtraSlashes(true);

        // Set unknown routes handler
        $router->notFound([
            'controller' => 'not-found',
            'action'=> 'notFound',
        ]);

        // Set router service
        $di->set('router', $router);
    }

    /**
     * Set dummy view
     *
     * @param \Phalcon\DI $di Dependency Injector
     */
    private function setView($di)
    {
        $di->setShared('view', new View());
    }

    /**
     * Constructor
     *
     * @param \Phalcon\Config\Ini $config Configuration settings
     */
    public function __construct($config)
    {
        $di = new FactoryDefault();
        parent::__construct($di);

        // Load config
        $di->set('config', $config);

        // Load components
        $this->setDispatcher($di);
        $this->setRoutes($di);
        $this->setView($di);

        // Enable output without view
        $this->view->disable();
    }

    /*
     * Run application and display output
     */
    public function run()
    {
        $response = $this->handle();
        echo $response->getContent();
    }
}
