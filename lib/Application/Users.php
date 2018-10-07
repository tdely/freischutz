<?php
namespace Freischutz\Application;

use Freischutz\Application\Exception;
use Phalcon\Mvc\User\Component;
use stdClass;

/**
 * Freischutz\Application\Users
 *
 * @see       https://gitlab.com/tdely/freischutz/ Freischutz on GitLab
 *
 * @author    Tobias Dély (tdely) <cleverhatcamouflage@gmail.com>
 * @copyright 2017-present Tobias Dély
 * @license   https://directory.fsf.org/wiki/License:BSD-3-Clause BSD-3-Clause
 */
class Users extends Component
{
    /** @var stdClass[string] Users loaded from backend. */
    private $userList;
    /** @var stdClass Current user as set from $userList. */
    private $user;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $doCache = in_array('users', array_map(
            'trim',
            explode(',', $this->config->application->get('cache_parts', false))
        ));

        if ($this->di->has('cache') && $doCache) {
            if ($this->userList = $this->cache->get('_freischutz_users')) {
                return;
            }
        }

        $backend = strtolower($this->config->application->get('users_backend', 'file'));
        switch ($backend) {
            case 'file':
                $userList = $this->loadFromFiles();
                break;
            case 'config':
                $userList = $this->loadFromConfig();
                break;
            case 'database':
            case 'db':
                $userList = $this->loadFromDatabase();
                break;
            default:
                throw new Exception("Unknown users backend: $backend");
        }

        if ($this->di->has('cache') && $doCache) {
            $this->cache->save('_freischutz_users', $userList);
        }

        $this->userList = $userList;
    }

    /**
     * Set user.
     *
     * User object matching given string $id is read from $userList property
     * and written to $user property.
     *
     * @internal
     * @param string $id Identifier of user to set.
     * @return bool
     */
    public function setUser(string $id):bool
    {
        if (!isset($this->userList[$id])) {
            $this->logger->debug("[Users] User ID '$id' not found.");
            return false;
        }

        $this->user = $this->userList[$id];

        return true;
    }

    /**
     * Get user.
     *
     * @return stdClass
     */
    public function getUser():stdClass
    {
        return $this->user;
    }

    /**
     * Load user details from files.
     *
     * @throws \Freischutz\Application\Exception
     * @return stdClass[string]
     */
    private function loadFromFiles():array
    {
        if (!isset($this->config->application->users_dir)) {
            throw new Exception(
                "Users backend 'file' requires users_dir set in application " .
                "section in config file."
            );
        }

        $userListDir = $this->config->application->app_dir .
            $this->config->application->users_dir;

        /**
         * Read user definitions
         */
        $userList = array();
        foreach (glob($userListDir . '/' . '*.users') as $file) {
            $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                // Allow comments starting with '#', ';', or '//'
                if ($line[0] === '#' || $line[0] === ';'
                        || substr($line, 0, 2) === '//') {
                    continue;
                }
                $parts = str_getcsv($line);
                // id,key
                if (sizeof($parts) !== 2) {
                    throw new Exception("Malformed row in $file: $line");
                }
                $userList[$parts[0]] = (object) array(
                    'id' => $parts[0],
                    'key' => $parts[1],
                );
            }
        }

        return $userList;
    }

    /**
     * Load user details from config.
     *
     * @throws \Freischutz\Application\Exception
     * @return stdClass[string]
     */
    private function loadFromConfig():array
    {
        if (!isset($this->config->users)) {
            throw new Exception(
                "Users backend 'config' requires section users in config file."
            );
        }

        $userList = array();
        foreach ($this->config->users as $id => $key) {
            $userList[$id] = (object) array('id' => $id, 'key' => $key);
        }

        return $userList;
    }

    /**
     * Load user details from database.
     *
     * @throws \Freischutz\Application\Exception
     * @return stdClass[string]
     */
    private function loadFromDatabase():array
    {
        if (!isset($this->config->application->users_model)) {
            throw new Exception(
                "Users backend 'database' requires users_model set in " .
                "application section in config file."
            );
        }

        $modelName = $this->config->application->users_model;
        if (!class_exists($modelName)) {
            throw new Exception("Users model not found: " . $modelName);
        }

        $model = new $this->config->application->users_model(null, $this->di);
        $metadata = $model->getModelsMetaData();

        $map = array(
            'id' => 'id',
            'keys' => array(
                'hawk_key' => 'key'
            )
        );

        if (method_exists($model, 'getAuthenticationMap')) {
            $mapLoaded = $model->getAuthenticationMap();

            $mapType = gettype($map);
            if ($mapType !== 'array') {
                throw new Exception(
                    "expected array got $mapType."
                );
            } else {
                $validIdTypes = array('string', 'int');
                if (isset($map['id'])
                        && array_search(gettype($map['id']), $validIdTypes)) {
                    $idType = gettype($map['id']);
                    throw new Exception(
                        "element 'id' expected string or int got $idType."
                    );
                } elseif (isset($map['keys']) && !is_array($map['keys'])) {
                    $keysType = gettype($map['keys']);
                    throw new Exception(
                        "element 'keys' expected array got $keysType."
                    );
                }
            }

            $map = array_replace_recursive($map, $mapLoaded);
        }

        foreach (array_values($map['keys']) as $key) {
            if (!$metadata->hasAttribute($model, $key)) {
                throw new Exception(
                    "Users model ('$modelName') missing column '$key'."
                );
            }
        }
        if (!$metadata->hasAttribute($model, $map['id'])) {
            throw new Exception(
                "Users model ('$modelName') missing column '{$map['id']}'."
            );
        }

        $userList = array();
        foreach ($model->find() as $row) {
            if (!isset($row->{$map['id']})) {
                throw new Exception(
                    "Users model ('$modelName') column '{$map['id']}' cannot be empty."
                );
            }

            $user = array(
                'id' => $row->{$map['id']},
            );
            $keys = array();

            foreach ($map['keys'] as $key => $attribute) {
                $keys[$key] = $row->$attribute;
            }
            $user['keys'] = (object) $keys;
            $userList[$row->{$map['id']}] = (object) $user;
        }

        return $userList;
    }
}
