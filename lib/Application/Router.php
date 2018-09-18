<?php
namespace Freischutz\Application;

use Freischutz\Application\Exception;
use Phalcon\Mvc\Router as PhalconRouter;
use Phalcon\Mvc\Router\Group;
use Phalcon\Mvc\User\Component;

/**
 * Freischutz\Application\Router
 *
 * @see       https://gitlab.com/tdely/freischutz/ Freischutz on GitLab
 *
 * @author    Tobias Dély (tdely) <cleverhatcamouflage@gmail.com>
 * @copyright 2017-present Tobias Dély
 * @license   https://directory.fsf.org/wiki/License:BSD-3-Clause BSD 3-clause "New" or "Revised" License
 *
 * @internal
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
     * @internal
     * @return \Phalcon\Mvc\Router
     */
    public function getRouter():PhalconRouter
    {
        return $this->router;
    }

    /**
     * Load routes from files.
     *
     * @throws \Freischutz\Application\Exception
     * @return \Phalcon\Mvc\Router\Group
     */
    private function loadFromFiles():Group
    {
        // Group routes for simplicity
        $group = new Group();
        $group->setPrefix($this->config->application->get('base_uri', ''));

        if (!isset($this->config->application->routes_dir)) {
            throw new Exception(
                "Missing 'routes_dir' in config application section."
            );
        }

        $routesDir = $this->config->application->app_dir .
            $this->config->application->routes_dir;

        /**
         * Load routes
         */
        $routeCount = 0;
        $fileCount = 0;
        foreach (glob($routesDir . "/*.routes") as $file) {
            $fileCount++;
            $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                // Allow comments starting with '#', ';', or '//'
                if ($line[0] === '#' || $line[0] === ';'
                        || substr($line, 0, 2) === '//') {
                    continue;
                }
                $parts = str_getcsv($line);
                if (sizeof($parts) !== 4) {
                    throw new Exception("Malformed row in $file: $line");
                }

                $group->add(
                    $parts[2],
                    array(
                        'controller' => $parts[0],
                        'action' => $parts[1],
                    ),
                    strtoupper($parts[3])
                );
                $routeCount++;
            }
        }
        $this->logger->debug(
            "[Router] $routeCount routes from $fileCount files loaded."
        );

        if (!$group->getRoutes()) {
            throw new Exception("No routes found in $routesDir");
        }

        return $group;
    }
}
