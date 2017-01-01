<?php
namespace Freischutz\Application;

use Phalcon\Mvc\Router as PhalconRouter;
use Phalcon\Mvc\Router\Group;
use Phalcon\Mvc\User\Component;

/**
 * Freischutz\Application\Router
 */
class Router extends Component
{
    private $router;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $doCache = in_array('routes', array_map(
            'trim',
            explode(',', $this->config->application->get('cache_parts', false))
        ));

        if ($this->di->has('cache') && $doCache) {
            $group = $this->cache->get('_freischutz_routes');
        }

        if (!isset($group)) {
            $group = $this->loadFromFiles();
            if ($this->di->has('cache') && $doCache) {
                $this->cache->save('_freischutz_routes', $group);
            }
        }

        /**
         * Set up router
         */
        $this->router = new PhalconRouter(false);
        $this->router->mount($group);

        // Remove trailing slashes
        $this->router->removeExtraSlashes(true);

        // Set unknown routes handler
        $this->router->notFound([
            'controller' => 'not-found',
            'action'=> 'notFound',
        ]);
    }

    /**
     * Get router.
     *
     * @return \Phalcon\Mvc\Router
     */
    public function getRouter()
    {
        return $this->router;
    }

    /**
     * Load routes from files.
     *
     * @throws \Exception if routes_dir not set in config application section.
     * @throws \Exception when no routes loaded or malformed route definition rows.
     * @return \Phalcon\Mvc\Router\Group
     */
    private function loadFromFiles()
    {
        // Group routes for simplicity
        $group = new Group();
        $group->setPrefix($this->config->application->get('base_uri', ''));

        if (!isset($this->config->application->routes_dir)) {
            throw new \Exception(
                "Missing 'routes_dir' in config application section."
            );
        }

        $routesDir = $this->config->application->app_dir .
            $this->config->application->routes_dir;

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

        if (!$group->getRoutes()) {
            throw new \Exception("No routes found in $routesDir");
        }

        return $group;
    }
}
