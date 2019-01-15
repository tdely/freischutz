<?php
namespace Freischutz\Utility;

use Phalcon\Mvc\Model;

/**
 * Extended model class for easy caching.
 *
 * @see       https://gitlab.com/tdely/freischutz/ Freischutz on GitLab
 *
 * @author    Tobias Dély (tdely) <cleverhatcamouflage@gmail.com>
 * @copyright 2017-present Tobias Dély
 * @license   https://directory.fsf.org/wiki/License:BSD-3-Clause BSD-3-Clause
 */
class CacheableModel extends Model
{
    /**
     * Create a cache key based on given key and model class name.
     *
     * @internal
     * @param int|string $key
     * @return string
     */
    protected static function createKey($key):string
    {
        $cpath = explode('\\', static::class);
        $class = strtolower(array_pop($cpath));

        return '_' . $class . ':' . $key;
    }

    /**
     * Create a default cache key from parameters.
     *
     * @internal
     * @param mixed $parameters
     * @return string
     */
    protected static function defaultKey($parameters):string
    {
        $uniqueKey = array();

        foreach ($parameters as $key => $value) {
            if (is_scalar($value)) {
                $uniqueKey[] = ($key !== 0 ? $key : 'condition') . ':' . $value;
            } elseif (is_array($value)) {
                $uniqueKey[] = $key . ':[' . self::defaultKey($value) . ']';
            }
        }

        return join(',', $uniqueKey);
    }

    /**
     * Remove an item cached through find/findFirst.
     *
     * @param int|string $key Key used to cache item.
     * @param string $service (optional) DI cache service.
     * @return void
     */
    public function uncache($key, string $service = 'cache')
    {
        if ($this->di->has($service)) {
            $this->di->get($service)->delete($this->createKey($key));
        }
    }

    /**
     * Query for a set of records that match the specified conditions.
     *
     * @param mixed $parameters
     * @return \Phalcon\Mvc\Model\ResultsetInterface
     */
    public static function find($parameters = null)
    {
        if ($parameters['cache']) {
            $key = $parameters['cache']['key']
                ? self::createKey($parameters['cache']['key'])
                : self::createKey(self::defaultKey($parameters));
            $parameters['cache'] = array(
                'key' => $key,
                'lifetime' => ($parameters['cache']['lifetime'] ?? 300),
                'service' => ($parameters['cache']['service'] ?? 'cache')
            );
        }

        return parent::find($parameters);
    }

    /**
     * Query the first record that matches the specified conditions.
     *
     * @param mixed $parameters
     * @return \Phalcon\Mvc\Model
     */
    public static function findFirst($parameters = null)
    {
        if (isset($parameters['cache'])) {
            $key = $parameters['cache']['key']
                ? self::createKey($parameters['cache']['key'])
                : self::createKey(self::defaultKey($parameters));
            $parameters['cache'] = array(
                'key' => $key,
                'lifetime' => ($parameters['cache']['lifetime'] ?? 300),
                'service' => ($parameters['cache']['service'] ?? 'cache')
            );
        }

        return parent::findFirst($parameters);
    }
}
