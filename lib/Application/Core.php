<?php
namespace Freischutz\Application;

use Freischutz\Application\Acl;
use Freischutz\Application\Exception;
use Freischutz\Application\Router;
use Freischutz\Application\Users;
use Freischutz\Security\Basic;
use Freischutz\Security\Hawk;
use Freischutz\Utility\Response;
use Phalcon\Cache\Frontend\Data as CacheData;
use Phalcon\DI;
use Phalcon\Events\Event;
use Phalcon\Events\Manager as EventsManager;
use Phalcon\Filter;
use Phalcon\Http\Request;
use Phalcon\Logger;
use Phalcon\Logger\Adapter\File as FileAdapter;
use Phalcon\Logger\Adapter\Syslog as SyslogAdapter;
use Phalcon\Logger\Formatter\Line as LineFormatter;
use Phalcon\Mvc\Application;
use Phalcon\Mvc\Dispatcher;
use Phalcon\Mvc\Model\Manager as ModelsManager;
use Phalcon\Mvc\View;

/**
 * Freischutz\Application\Core
 *
 * @see       https://gitlab.com/tdely/freischutz/ Freischutz on GitLab
 *
 * @author    Tobias Dély (tdely) <cleverhatcamouflage@gmail.com>
 * @copyright 2017-present Tobias Dély
 * @license   https://directory.fsf.org/wiki/License:BSD-3-Clause BSD 3-clause "New" or "Revised" License
 */
class Core extends Application
{
    const VERSION = '0.5.0';

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
     * Set logger service.
     *
     * @param \Phalcon\DI $di Dependency Injector.
     * @return void
     */
    public function setLogger($di)
    {
        $destination = $this->config->application->get('log_destination', 'syslog');
        $level = $this->config->application->get('log_level', 'error');
        $name = $this->config->application->get('log_name', 'freischutz');

        if ($destination === 'syslog') {
            $logger = new SyslogAdapter(
                $name,
                array(
                    'option' => LOG_CONS | LOG_NDELAY | LOG_PID,
                    'facility' => LOG_USER,
            ));
        } else {
            $pid = getmypid();
            $logger = new FileAdapter($destination);
            $logger->setFormatter(
                new LineFormatter("[%date%] {$name}[$pid] [%type%] %message%")
            );
        }

        switch (strtolower($level)) {
            case 'debug':
                $level = Logger::DEBUG;
                break;
            case 'info':
                $level = Logger::INFO;
                break;
            case 'notice':
                $level = Logger::NOTICE;
                break;
            case 'warning':
                $level = Logger::WARNING;
                break;
            case 'error':
                $level = Logger::ERROR;
                break;
            case 'alert':
                $level = Logger::ALERT;
                break;
            case 'critical':
                $level = Logger::CRITICAL;
                break;
            case 'emergency':
                $level = Logger::EMERGENCY;
                break;
            default:
                $level = Logger::ERROR;
                break;
        }

        $logger->setLogLevel($level);

        $di->setShared('logger', $logger);
    }

    /**
     * Set request service.
     *
     * @param \Phalcon\DI $di Dependency Injector.
     * @return void
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
     * @return void
     */
    private function setFilter($di)
    {
        $di->setShared('filter', new Filter());
    }

    /**
     * Set dispatcher service.
     *
     * @param \Phalcon\DI $di Dependency Injector.
     * @return void
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
     * @return void
     */
    private function setCache($di)
    {
        if (!isset($this->config->application->cache_adapter)
                || !$this->config->application->cache_adapter) {
            return;
        }

        $adapterName = $this->config->application->cache_adapter;
        $adapterClass = '\\Phalcon\\Cache\\Backend\\' . $adapterName;

        $frontCache = new CacheData(array(
            'lifetime' => $this->config->application->get('cache_lifetime', 300)
        ));
        $cache = new $adapterClass(
            $frontCache,
            (array) $this->config->{strtolower($adapterName)}
        );
        $di->set('cache', $cache);
    }

    /**
     * Set router service.
     *
     * @param \Phalcon\DI $di Dependency Injector.
     * @return void
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
     * @throws \Freischutz\Application\Exception
     * @param \Phalcon\DI $di Dependency Injector.
     * @return void
     */
    private function setDatabases($di)
    {
        if (!isset($this->config->application->databases_dir)) {
            throw new \Freischutz\Application\Exception(
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
            if (!in_array($adapter, ['Mysql', 'Postgresql', 'Sqlite'])) {
                throw new \Freischutz\Application\Exception(
                    "Unexpected database adapter in $file: $adapter"
                );
            }
            $adapter = "\\Phalcon\\Db\\Adapter\\Pdo\\$adapter";
            $connection = new $adapter($config);

            // Set database service
            $di->set($alias, $connection);
        }
    }

    /**
     * Set data container with request data.
     *
     * @param \Phalcon\DI $di Dependency Injector.
     * @return void
     */
    private function setData($di)
    {
        $di->set('data', new Data(file_get_contents('php://input')));
    }

    /**
     * Set models manager.
     *
     * @param \Phalcon\DI $di Dependency Injector.
     * @return void
     */
    private function setModelsManager($di)
    {
        $di->set('modelsManager', new ModelsManager());
    }

    /**
     * Set models metadata.
     *
     * @param \Phalcon\DI $di Dependency Injector.
     * @return void
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
     * @return void
     */
    private function setUsers($di)
    {
        $di->set('users', new Users());
    }

    /**
     * Set authentication through Hawk.
     *
     * @param \Phalcon\Events\Manager $eventsManager Events manager.
     * @throws \Freischutz\Application\Exception
     * @return void
     */
    private function authenticateHawk(EventsManager $eventsManager)
    {
        if (!isset($this->config->hawk)) {
            throw new \Freischutz\Application\Exception(
                "Hawk authentication requires hawk section in config file."
            );
        }

        $eventsManager->attach(
            "application:beforeHandleRequest",
            function (Event $event, $dispatcher) {
                $this->hawk = new Hawk();

                if ($this->users->setUser($this->hawk->getParam('id'))) {
                    $user = $this->users->getUser();
                    if (isset($user->keys->hawk_key)) {
                        $this->logger->debug('[Core] Using user->keys->hawk_key');
                        $key = $user->keys->hawk_key;
                    } else {
                        $this->logger->debug('[Core] Using user->key');
                        $key = $user->key;
                    }
                    $this->hawk->setKey($key);
                    $result = $this->hawk->authenticate();
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
     * Set basic authentication.
     *
     * @param \Phalcon\Events\Manager $eventsManager Events manager.
     * @throws \Freischutz\Application\Exception
     * @return void
     */
    private function authenticateBasic(EventsManager $eventsManager)
    {
        if (!isset($this->config->basic_auth)) {
            throw new \Freischutz\Application\Exception(
                "Basic authentication requires basic_auth section in config file."
            );
        }

        $eventsManager->attach(
            "application:beforeHandleRequest",
            function (Event $event, $dispatcher) {
                $basic = new Basic();

                if ($this->users->setUser($basic->getUser())) {
                    $user = $this->users->getUser();
                    if (isset($user->keys->basic_key)) {
                        $this->logger->debug('[Core] Using user->keys->basic_key');
                        $key = $user->keys->basic_key;
                    } else {
                        $this->logger->debug('[Core] Using user->key');
                        $key = $user->key;
                    }
                    $basic->setKey($key);
                    $result = $basic->authenticate();
                } else {
                    $result = (object) array(
                        'message' => 'Request not authentic.',
                        'state' => false
                    );
                }

                $message = $this->config->basic_auth->get('disclose', false)
                    ? $result->message
                    : 'Authentication failed.';

                $realm = $this->config->basic_auth->get('realm', 'freischutz');

                if (!$result->state) {
                    $header = 'Basic realm="' . $realm . '"';
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
     *
     * @return void
     */
    private function setEventsManagers()
    {
        $eventsManager = new EventsManager();

        /**
         * Authentication
         */
        $authenticate = $this->config->application->get('authenticate', false);
        if ($authenticate) {
            // Get allowed authentication mechanisms
            $mechanisms = array_map('trim', explode(',', $authenticate));

            // Get requested authentication mechanism
            $reqMechanism = array();
            preg_match(
                '/(.+?)(\s|,)/',
                $this->request->getHeader('Authorization'),
                $reqMechanism
            );
            $reqMechanism = isset($reqMechanism[1]) ? $reqMechanism[1] : false;
            $acceptedMechanism = in_array(
                strtolower($reqMechanism),
                array_map('strtolower', $mechanisms)
            );
            if (!$reqMechanism || !$acceptedMechanism) {
                /**
                 * Illegal authentication mechanism
                 */
                $header = implode(', ', $mechanisms);
                $eventsManager->attach(
                    "application:beforeHandleRequest",
                    function (Event $event, $dispatcher) use ($reqMechanism, $header) {
                        $this->response->unauthorized(
                            "Illegal authentication mechanism: $reqMechanism",
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
                    case 'basic':
                        $this->authenticateBasic($eventsManager);
                        break;
                    default:
                        throw new \Freischutz\Application\Exception(
                            "Unknown authentication mechanism: $reqMechanism"
                        );
                }
            }
        }

        /**
         * ACL
         */
        if (isset($this->config->acl)
                && $this->config->acl->get('enable', false)) {
            $eventsManager->attach(
                "dispatch:beforeExecuteRoute",
                function (Event $event, $dispatcher) {
                    $controller = $this->dispatcher->getControllerName();
                    $action = $this->dispatcher->getActionName();

                    $client = $this->users->getUser();

                    $acl = new Acl;
                    $access = $acl->isAllowed($client->id, $controller, $action);

                    if (!$access) {
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
     * @return void
     */
    private function setView($di)
    {
        $di->setShared('view', new View());
    }

    /**
     * Constructor.
     *
     * @throws \Freischutz\Application\Exception
     * @param \Phalcon\Config\Ini $config Configuration settings.
     * @return void
     */
    public function __construct($config)
    {
        try {
            if (!isset($config->application->app_dir)) {
                throw new Exception(
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
            $this->setLogger($di);
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
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
            throw $e;
        }
    }

    /**
     * Run application and display output.
     *
     * @return void
     */
    public function run()
    {
        $response = $this->handle();
        if (gettype($response) === 'object') {
            if (isset($this->hawk) && method_exists($response, 'getContentType')) {
                $header = $this->hawk->validateResponse();
                $response->setHeader('Server-Authorization', $header);
            }
            $response->send();
        }
    }
}
