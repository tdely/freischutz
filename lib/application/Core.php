<?php
namespace Freischutz\Application;

use Phalcon\DI;
use Phalcon\Http\Request;
use Phalcon\Mvc\Application;
use Phalcon\Mvc\Dispatcher;
use Phalcon\Mvc\View;
use Phalcon\Mvc\Router;
use Phalcon\Mvc\Router\Group;
use Phalcon\Db\Adapter\Pdo\Mysql;
use Phalcon\Db\Adapter\Pdo\Postgresql;
use Phalcon\Db\Adapter\Pdo\Sqlite;

/**
 * Freischutz\Application\Core
 */
class Core extends Application
{

    /**
     * Set request service
     *
     * @param \Phalcon\DI $di Dependency Injector
     */
    private function setRequest($di)
    {
        $request = new Request();
        if ($this->config->application->get('strict_host_check', false)) {
            $request->setStrictHostCheck(true);
        }

        $di->setShared('request', function() use ($request) {
            return $request;
        });
    }

    /**
     * Set dispatcher service
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

    /*
     * Set databases
     *
     * @throws \Exception on unknown database adapter in loaded database config
     * @param \Phalcon\DI $di Dependency Injector
     */
    private function setDatabases($di)
    {
        // Get directory path
        $dir = $this->config->application->app_dir .
            $this->config->application->databases_dir;

        /**
         * Load databases
         */
        foreach (glob($dir . '/*.php') as $file) {
            $alias = basename($file, '.php');
            $alias = (strtolower($alias) === 'default') ? 'db' : 'db' . $alias;
            $config = include $file;

            $adapter = ucfirst(strtolower($config['adapter']));
            unset($config['adapter']);

            // Validate adapter
            if (!in_array($adapter, array('Mysql', 'Postgresql', 'Sqlite', true))) {
                throw new \Exception(
                    "Unexpected database adapter in $file: $adapter"
                );
            }
            $adapter = "\\Phalcon\\Db\\Adapter\\Pdo\\$adapter";

            // Set database service
            $di->set($alias, new $adapter($config));
        }
    }

    /**
     * Set data container with request data
     *
     * @param \Phalcon\DI $di Dependency Injector
     */
    private function setData($di)
    {
        $di->set('data', new Data(file_get_contents('php://input')));
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
        $di = new DI();
        parent::__construct($di);

        // Load config
        $di->set('config', $config);

        // Load components
        $this->setRequest($di);
        $this->setDispatcher($di);
        $this->setRoutes($di);
        $this->setDatabases($di);
        $this->setData($di);
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
