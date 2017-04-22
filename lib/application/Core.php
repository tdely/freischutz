<?php
namespace Freischutz\Application;

use Freischutz\Application\Acl;
use Freischutz\Application\Router;
use Freischutz\Application\Users;
use Freischutz\Security\Hawk;
use Freischutz\Utility\Response;
use Phalcon\Cache\Frontend\Data as CacheData;
use Phalcon\DI;
use Phalcon\Events\Event;
use Phalcon\Events\Manager as EventsManager;
use Phalcon\Filter;
use Phalcon\Http\Request;
use Phalcon\Mvc\Application;
use Phalcon\Mvc\Dispatcher;
use Phalcon\Mvc\Model\Manager as ModelsManager;
use Phalcon\Mvc\View;

/**
 * Freischutz\Application\Core
 */
class Core extends Application
{
    const VERSION = '0.4.0';

    /**
     * Get Freischutz version.
     *
     * @return string
     */
    public static function getVersion()
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

        $di->setShared('request', function () use ($request) {
            return $request;
        });
    }


    /**
     * Set filter service.
     *
     * @param \Phalcon\DI $di Dependency Injector.
     */
    private function setFilter($di)
    {
        $di->setShared('filter', new Filter());
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
     * Set cache service.
     *
     * @param \Phalcon\DI $di Dependency Injector.
     */
    private function setCache($di)
    {
        if (!isset($this->config->application->cache_adapter)
                || !$this->config->application->cache_adapter) {
            return;
        }

        $adapterClass = '\\Phalcon\\Cache\\Backend\\' .
            $this->config->application->cache_adapter;

        $adapterName = $this->config->application->cache_adapter;

        $frontCache = new CacheData(array(
            'lifetime' => $this->config->application->get('cache_lifetime', 300)
        ));
        $cache = new $adapterClass($frontCache, (array) $this->config->$adapterName);
        $di->set('cache', $cache);
    }

    /**
     * Set router service.
     *
     * @param \Phalcon\DI $di Dependency Injector.
     */
    private function setRouter($di)
    {
        $router = new Router(false);

        // Set router service
        $di->set('router', $router->getRouter());
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
        $adapterClass = '\\Phalcon\\Mvc\\Model\\MetaData\\' .
            $this->config->application->get('metadata_adapter', 'Memory');

        $adapterName = $this->config->application->get('metadata_adapter', 'Memory');
        $configSection = "metadata_$adapterName";

        $config = isset($this->config->$configSection)
            ? (array) $this->config->$configSection
            : array();

        $modelsMetadata = new $adapterClass($config);

        $di->set('modelsMetadata', $modelsMetadata);
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
     * Set authentication through Hawk.
     *
     * @param \Phalcon\Events\Manager $eventsManager Events manager.
     * @throws \Exception if config hawk section missing.
     * @return void
     */
    private function authenticateHawk(EventsManager $eventsManager)
    {
        if (!isset($this->config->hawk)) {
            throw \Exception(
                "Hawk authentication requires hawk section in config file."
            );
        }

        $eventsManager->attach(
            "application:beforeHandleRequest",
            function (Event $event, $dispatcher) {
                $hawk = new Hawk();

                if ($this->users->setUser($hawk->getParam('id'))) {
                    $hawk->setKey($this->users->getUser()->key);
                    $result = $hawk->authenticate();
                } else {
                    $result = (object) array(
                        'message' => 'Request not authentic.',
                        'state' => false
                    );
                }

                $message = $this->config->hawk->get('disclose', false)
                    ? $result->message
                    : 'Authentication failed.';

                if (!$result->state) {
                    $header = 'Hawk ts="' . date('U') . '", ' .
                              'alg="' . $this->config->hawk->algorithms . '"';
                    $this->response->unauthorized($message, $header);
                    $this->response->send();
                }

                return $result->state;
            }
        );
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
         * Authentication
         */
        if ($authenticate = $this->config->application->get('authenticate', false)) {
            // Get allowed authentication mechanisms
            $mechanisms = array_map('trim', explode(',', $authenticate));

            // Get requested authentication mechanism
            $reqMechanism = array();
            preg_match('/(.+?)(\s|,)/', $this->request->getHeader('Authorization'), $reqMechanism);
            $reqMechanism = isset($reqMechanism[1]) ? $reqMechanism[1] : false;

            if (!$reqMechanism || !in_array(strtolower($reqMechanism), array_map('strtolower', $mechanisms))) {
                /**
                 * Illegal authentication mechanism
                 */
                $header = implode(', ', $mechanisms);
                $eventsManager->attach(
                    "application:beforeHandleRequest",
                    function (Event $event, $dispatcher) use ($reqMechanism, $header) {
                        $this->response->unauthorized(
                            'Illegal authentication mechanism: ' . $reqMechanism,
                            $header
                        );
                        $this->response->send();
                        return false;
                    }
                );
            } else {
                /**
                 * Load authentication mechanism
                 */
                switch (strtolower($reqMechanism)) {
                    case 'hawk':
                        $this->authenticateHawk($eventsManager);
                        break;
                    default:
                        throw \Exception('Unknown authentication mechanism: ' . $reqMechanism);
                }
            }
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
                "Missing 'app_dir' to be set in config application section."
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
        $this->setFilter($di);
        $this->setDispatcher($di);
        $this->setCache($di);
        $this->setRouter($di);
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

    /**
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
