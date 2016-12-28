<?php
namespace Freischutz\Application;

use Freischutz\Application\Users;
use Freischutz\Application\Acl;
use Freischutz\Security\Hawk;
use Freischutz\Utility\Response;
use Phalcon\Db\Adapter\Pdo\Mysql;
use Phalcon\Db\Adapter\Pdo\Postgresql;
use Phalcon\Db\Adapter\Pdo\Sqlite;
use Phalcon\DI;
use Phalcon\Events\Event;
use Phalcon\Events\Manager as EventsManager;
use Phalcon\Http\Request;
use Phalcon\Mvc\Application;
use Phalcon\Mvc\Dispatcher;
use Phalcon\Mvc\Model\Manager as ModelsManager;
use Phalcon\Mvc\Model\MetaData\Memory as ModelsMetadata;
use Phalcon\Mvc\Router;
use Phalcon\Mvc\Router\Group;
use Phalcon\Mvc\View;

/**
 * Freischutz\Application\Core
 */
class Core extends Application
{
    const VERSION = '0.1.0';

    /**
     * Get Freischutz version.
     *
     * @return string
     */
    public function getVersion()
    {
        return self::VERSION;
    }

    /**
     * Set request service.
     *
     * @param \Phalcon\DI $di Dependency Injector.
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
     * Set events managers.
     *
     * Attaches event listeners to events manager, and sets the event manager
     * to be used by certain components.
     */
    private function setEventsManagers()
    {
        $eventsManager = new EventsManager();
        /**
         * HMAC
         */
        if (isset($this->config->hawk) && $this->config->hawk->get('enable', false)) {
            $eventsManager->attach(
                "application:beforeHandleRequest",
                function (Event $event, $dispatcher) {
                    $hmac = new Hawk();

                    $return = false;

                    if ($this->users->setUser($hmac->getParam('id'))) {
                        $hmac->setKey($this->users->getUser()->key);
                        $result = $hmac->authenticate();
                        $return = $result->state;
                    }

                    $message = $this->config->hawk->get('disclose', false)
                        ? $result->message
                        : 'Authentication failed.';

                    if (!$return) {
                        $header = 'Hawk ts="' . date('U') . '", ' .
                                  'alg="' . $this->config->hawk->algorithms . '"';
                        $this->response->unauthorized($message, $header);
                        $this->response->send();
                    }

                    return $return;
                }
            );
        }
        /**
         * ACL
         */
        if (isset($this->config->acl) && $this->config->acl->get('enable', false)) {
            $eventsManager->attach(
                "dispatch:beforeExecuteRoute",
                function (Event $event, $dispatcher) {
                    $controller = $this->dispatcher->getControllerName();
                    $action = $this->dispatcher->getActionName();

                    $client = $this->users->getUser();

                    $acl = new Acl;
                    if (!$access = $acl->isAllowed($client->id, $controller, $action)) {
                        $this->response->forbidden('Access denied.');
                        $this->response->send();
                    }

                    return $access;
                }
            );
        }
        $this->setEventsManager($eventsManager);
        $this->dispatcher->setEventsManager($eventsManager);
    }

    /**
     * Set dispatcher service.
     *
     * @param \Phalcon\DI $di Dependency Injector.
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
     * Set routes.
     *
     * @throws \Exception if routes_dir not set in config application section.
     * @throws \Exception when no routes loaded or malformed route definition rows.
     * @param \Phalcon\DI $di Dependency Injector.
     */
    private function setRoutes($di)
    {
        if (!isset($this->config->application->routes_dir)) {
            throw new \Exception(
                "Missing 'routes_dir' in config application section."
            );
        }

        $routesDir = $this->config->application->app_dir .
            $this->config->application->routes_dir;

        // Group routes for simplicity
        $group = new Group();
        $group->setPrefix($this->config->application->get('base_uri', ''));

        $routes = array();

        /**
         * Load routes
         */
        foreach (glob($routesDir . "/*.routes") as $file) {
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
     * Set databases.
     *
     * @throws \Exception if databases_dir not set in config application section.
     * @throws \Exception on unknown database adapter in loaded database config.
     * @param \Phalcon\DI $di Dependency Injector.
     */
    private function setDatabases($di)
    {
        if (!isset($this->config->application->databases_dir)) {
            throw new \Exception(
                "Missing 'databases_dir' in config application section."
            );
        }

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
     * Set data container with request data.
     *
     * @param \Phalcon\DI $di Dependency Injector.
     */
    private function setData($di)
    {
        $di->set('data', new Data(file_get_contents('php://input')));
    }

    /**
     * Set users.
     *
     * @param \Phalcon\DI $di Dependency Injector.
     */
    private function setUsers($di)
    {
        $di->set('users', new Users());
    }

    /**
     * Set models manager.
     *
     * @param \Phalcon\DI $di Dependency Injector.
     */
    private function setModelsManager($di)
    {
        $di->set('modelsManager', new ModelsManager());
    }

    /**
     * Set models metadata.
     *
     * @param \Phalcon\DI $di Dependency Injector.
     */
    private function setModelsMetadata($di)
    {
        // TODO: Make metadata storage configurable
        $di->set('modelsMetadata', new ModelsMetadata());
    }

    /**
     * Set dummy view.
     *
     * @param \Phalcon\DI $di Dependency Injector.
     */
    private function setView($di)
    {
        $di->setShared('view', new View());
    }

    /**
     * Constructor.
     *
     * @throws \Exception if $config->application->app_dir not set.
     * @param \Phalcon\Config\Ini $config Configuration settings.
     */
    public function __construct($config)
    {
        if (!isset($config->application->app_dir)) {
            throw new \Exception(
                "Core requires '\$config->application->app_dir' being set."
            );
        }

        $di = new DI();
        parent::__construct($di);

        // Load config
        $di->set('config', $config);

        // Pre-load response
        $di->set('response', new Response());

        // Load components
        $this->setRequest($di);
        $this->setDispatcher($di);
        $this->setRoutes($di);
        $this->setDatabases($di);
        $this->setData($di);
        $this->setModelsManager($di);
        $this->setModelsMetadata($di);
        $this->setUsers($di);
        $this->setEventsManagers($di);
        $this->setView($di);

        // Enable output without view
        $this->view->disable();
    }

    /*
     * Run application and display output.
     */
    public function run()
    {
        $response = $this->handle();
        if (gettype($response) === 'object') {
            echo $response->getContent();
        }
    }
}
