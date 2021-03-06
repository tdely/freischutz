<?php
namespace Freischutz\Application;

use Freischutz\Security\Basic;
use Freischutz\Security\Hawk;
use Freischutz\Security\Jwt;
use Freischutz\Utility\Base64url;
use Freischutz\Utility\Response;
use Phalcon\Cache\Frontend\Data as CacheData;
use Phalcon\Config\Adapter\Ini;
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
 * Freischutz application core.
 *
 * @see       https://gitlab.com/tdely/freischutz/ Freischutz on GitLab
 *
 * @author    Tobias Dély (tdely) <cleverhatcamouflage@gmail.com>
 * @copyright 2017-present Tobias Dély
 * @license   https://directory.fsf.org/wiki/License:BSD-3-Clause BSD-3-Clause
 */
class Core extends Application
{
    /** @var string Freischutz version number. */
    const VERSION = '0.9.4';

    /**
     * Get Freischutz version.
     *
     * @return string
     */
    public static function getVersion():string
    {
        return self::VERSION;
    }

    /**
     * Set logger service.
     *
     * @param \Phalcon\DI $di Dependency Injector.
     * @return void
     */
    private function setLogger(DI $di)
    {
        $destination = $this->config->application->get(
            'log_destination',
            'syslog'
        );
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
    private function setRequest(DI $di)
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
    private function setFilter(DI $di)
    {
        $di->setShared('filter', new Filter());
    }

    /**
     * Set dispatcher service.
     *
     * @param \Phalcon\DI $di Dependency Injector.
     * @return void
     */
    private function setDispatcher(DI $di)
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
    private function setCache(DI $di)
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
     * @throws \Freischutz\Application\Exception
     * @param \Phalcon\DI $di Dependency Injector.
     * @return void
     */
    private function setRouter(DI $di)
    {
        $router = new Router();

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
    private function setDatabases(DI $di)
    {
        if (!isset($this->config->application->databases_dir)) {
            throw new Exception(
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
                throw new Exception(
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
    private function setData(DI $di)
    {
        $di->set('data', new Data(file_get_contents('php://input')));
    }

    /**
     * Set models manager.
     *
     * @param \Phalcon\DI $di Dependency Injector.
     * @return void
     */
    private function setModelsManager(DI $di)
    {
        $di->set('modelsManager', new ModelsManager());
    }

    /**
     * Set models metadata.
     *
     * @param \Phalcon\DI $di Dependency Injector.
     * @return void
     */
    private function setModelsMetadata(DI $di)
    {
        $adapterClass = '\\Phalcon\\Mvc\\Model\\MetaData\\' .
            $this->config->application->get('metadata_adapter', 'Memory');

        $adapterName = $this->config->application->get(
            'metadata_adapter',
            'Memory'
        );
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
     * @throws \Freischutz\Application\Exception
     * @param \Phalcon\DI $di Dependency Injector.
     * @return void
     */
    private function setUsers(DI $di)
    {
        $di->set('users', new Users());
    }

    /**
     * Set authentication through Hawk.
     *
     * @throws \Freischutz\Application\Exception
     * @param \Phalcon\Events\Manager $eventsManager Events manager.
     * @return void
     */
    private function authenticateHawk(EventsManager $eventsManager)
    {
        if (!isset($this->config->hawk)) {
            throw new Exception(
                'Hawk authentication requires hawk section in config file.'
            );
        }

        $eventsManager->attach(
            'application:beforeHandleRequest',
            function () {
                $this->hawk = new Hawk();

                if (!$this->hawk->getParam('id')) {
                    $result = (object) array(
                        'message' => 'No user provided in request.',
                        'state' => false
                    );
                } elseif ($this->users->setUser($this->hawk->getParam('id'))) {
                    $user = $this->users->getUser();
                    if (isset($user->keys->hawk_key)) {
                        $this->logger->debug(
                            '[Core] Using user->keys->hawk_key'
                        );
                        $key = $user->keys->hawk_key;
                    } else {
                        $this->logger->debug('[Core] Using user->key');
                        $key = $user->key;
                    }
                    if ($key) {
                        $this->hawk->setKey($key);
                    }
                    $result = $this->hawk->authenticate();
                } else {
                    $result = (object) array(
                        'message' => 'User does not exist.',
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
     * @throws \Freischutz\Application\Exception
     * @param \Phalcon\Events\Manager $eventsManager Events manager.
     * @return void
     */
    private function authenticateBasic(EventsManager $eventsManager)
    {
        if (!isset($this->config->basic_auth)) {
            throw new Exception(
                'Basic authentication requires basic_auth section in config ' .
                'file.'
            );
        }

        $eventsManager->attach(
            'application:beforeHandleRequest',
            function () {
                $basic = new Basic();

                if (!$basic->getUser()) {
                    $result = (object) array(
                        'message' => 'No user provided in request.',
                        'state' => false
                    );
                } elseif ($this->users->setUser($basic->getUser())) {
                    $user = $this->users->getUser();
                    if (isset($user->keys->basic_key)) {
                        $this->logger->debug('[Core] Using user->keys->basic_key');
                        $key = $user->keys->basic_key;
                    } else {
                        $this->logger->debug('[Core] Using user->key');
                        $key = $user->key;
                    }
                    if ($key) {
                        $basic->setKey($key);
                    }
                    $result = $basic->authenticate();
                } else {
                    $result = (object) array(
                        'message' => 'User does not exist.',
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
     * Set Bearer authentication.
     *
     * @throws \Freischutz\Application\Exception
     * @param \Phalcon\Events\Manager $eventsManager Events manager.
     * @return void
     */
    private function authenticateBearer(EventsManager $eventsManager)
    {
        if (!isset($this->config->bearer)) {
            throw new Exception(
                'Bearer token authentication requires bearer section in config file.'
            );
        }

        $eventsManager->attach(
            'application:beforeHandleRequest',
            function () {
                $token = substr($this->request->getHeader('Authorization'), 7);

                $result = (object) array(
                    'message' => '',
                    'state' => false
                );

                $types = explode(',', $this->config->bearer->get('types', 'jwt'));

                if (!isset($token)) {
                    $result->message = 'Missing bearer token.';
                } elseif (!$header = substr($token, 0, strpos($token, '.'))) {
                    $result->message = 'Bearer token is malformed.';
                } elseif (!$header = json_decode(Base64url::decode($header))) {
                    $result->message = 'Bearer token header is malformed.';
                } elseif (!isset($header->typ)) {
                    $result->message = 'Bearer token header has no type.';
                } elseif (!in_array(strtolower($header->typ), $types)) {
                    $result->message = 'Bearer type not allowed.';
                } else {
                    switch (strtolower($header->typ)) {
                        case 'jwt':
                            $bearer = new Jwt();
                            $bearerKeyName = 'jwt_key';
                            break;
                        default:
                            $result->message = 'Unsupported bearer type.';
                            break;
                    }
                }

                if (!empty($result->message)) {
                    $this->response->unauthorized($result->message, 'Bearer');
                    $this->response->send();
                    return false;
                }

                if (!$bearer->getUser()) {
                    $result = (object) array(
                        'message' => 'Token subject empty.',
                        'state' => false
                    );
                } elseif ($this->users->setUser($bearer->getUser())) {
                    $user = $this->users->getUser();
                    if (isset($user->keys->$bearerKeyName)) {
                        $this->logger->debug(
                            "[Core] Using user->keys->$bearerKeyName"
                        );
                        $key = $user->keys->$bearerKeyName;
                    } else {
                        $this->logger->debug('[Core] Using user->key');
                        $key = $user->key;
                    }
                    if ($key) {
                        $bearer->setKey($key);
                    }
                    $result = $bearer->authenticate();
                } else {
                    $result = (object) array(
                        'message' => 'User does not exist.',
                        'state' => false
                    );
                }

                $message = $this->config->bearer->get('disclose', false)
                    ? $result->message
                    : 'Authentication failed.';

                if (!$result->state) {
                    $this->response->unauthorized($message, 'Bearer');
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
     * @throws \Freischutz\Application\Exception
     * @param \Phalcon\DI $di Dependency Injector.
     * @return void
     */
    private function setEventsManagers(DI $di)
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
            $reqMechanism = $reqMechanism[1] ?? false;
            $acceptedMechanism = in_array(
                ($reqMechanism ? strtolower($reqMechanism) : false),
                array_map('strtolower', $mechanisms)
            );
            if (!$reqMechanism || !$acceptedMechanism) {
                /**
                 * Illegal authentication mechanism
                 */
                $header = implode(', ', $mechanisms);
                $eventsManager->attach(
                    'application:beforeHandleRequest',
                    function () use ($reqMechanism, $header) {
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
                    case 'bearer':
                        $this->authenticateBearer($eventsManager);
                        break;
                    default:
                        throw new Exception(
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
            $acl = new Acl();
            if ($this->config->acl->get('di_share', false)) {
                $di->setShared('acl', $acl);
            }
            $eventsManager->attach(
                'dispatch:beforeExecuteRoute',
                function () use ($acl) {
                    $controller = $this->dispatcher->getControllerName();
                    $action = $this->dispatcher->getActionName();

                    $client = $this->users->getUser();

                    $access = $acl->isAllowed($client->id, $controller, $action);

                    if (!$access) {
                        // Response content set here doesn't show, workaround in self::run()
                        $this->response->forbidden('Access denied.');
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
    private function setView(DI $di)
    {
        $di->setShared('view', new View());
    }

    /**
     * Core constructor.
     *
     * @throws \Freischutz\Application\Exception
     * @param \Phalcon\Config\Adapter\Ini $config Configuration settings.
     */
    public function __construct(Ini $config)
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
            // Handle odd ACL event behavior
            if ($response->getStatusCode() === 403 && !$response->getContent()) {
                $response->forbidden('Access denied.');
            }
            if (isset($this->hawk) && method_exists($response, 'getContentType')) {
                $header = $this->hawk->validateResponse();
                $response->setHeader('Server-Authorization', $header);
            }
            $response->send();
        }
    }
}
