<?php
namespace Freischutz\Application;

use Phalcon\Mvc\User\Component;

/**
 * Freischutz\Application\Users
 */
class Users extends Component
{
    private $userList;
    private $user;

    /**
     * Constructor.
     *
     * Loads user list from configured backend (default file) into $userList
     * property.
     *
     * @throw \Exception on unknown backend.
     * @return void
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
                throw new \Exception("Unknown users backend: $backend");
        }

        if ($this->di->has('cache') && $doCache) {
            $this->cache->save('_freischutz_users', (object) $userList);
        }

        $this->userList = (object) $userList;
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
    public function setUser($id)
    {
        if (!isset($this->userList->$id)) {
            return false;
        }

        $this->user = $this->userList->$id;

        return true;
    }

    /**
     * Get user.
     *
     * @return object
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Load user details from files.
     *
     * @throw \Exception if users_dir not set in config application section.
     * @throw \Exception if encountering a malformed line.
     * @return array
     */
    private function loadFromFiles()
    {
        if (!isset($this->config->application->users_dir)) {
            throw new \Exception(
                "Users backend 'file' requires users_dir set in application " .
                "section in config file."
            );
        }

        $userListDir = $this->config->application->app_dir .
            $this->config->application->users_dir;

        /**
         * Read user definitions
         */
        foreach (glob($userListDir . '/' . '*.users') as $file) {
            $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                // Allow comments starting with '#', ';', or '//'
                if ($line[0] === '#' || $line[0] === ';'
                        || substr($line, 0, 2) === '//') {
                    continue;
                }
                $parts = explode(',', $line);
                // id,key
                if (sizeof($parts) !== 2) {
                    throw new \Exception("Malformed row in $file: $line");
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
     * @throw \Exception if users sections in config is missing and/or contains
     *   no items.
     * @return array
     */
    private function loadFromConfig()
    {
        if (!isset($this->config->users)) {
            throw new \Exception(
                "Users backend 'config' requires section users in config file."
            );
        }

        foreach ($this->config->users as $id => $key) {
            $userList[$id] = (object) array('id' => $id, 'key' => $key);
        }

        return $userList;
    }

    /**
     * Load user details from database.
     *
     * @throw \Exception if users_model not set in config application section.
     * @throw \Exception if model cannot be found.
     * @throw \Exception if model doesn't contain attributes id and key.
     * @throw \Exception if model id is empty.
     * @return array
     */
    private function loadFromDatabase()
    {
        if (!isset($this->config->application->users_model)) {
            throw new \Exception(
                "Users backend 'database' requires users_model set in " .
                "application section in config file."
            );
        }

        $modelName = $this->config->application->users_model;
        if (!class_exists($modelName)) {
            throw new \Exception("Users model not found: " . $modelName);
        }

        $model = new $this->config->application->users_model(null, $this->di);
        $metadata = $model->getModelsMetaData();

        if (!$metadata->hasAttribute($model, 'id')
                || !$metadata->hasAttribute($model, 'key')) {
            throw new \Exception(
                "Users model must contain columns 'id' and 'key'."
            );
        }

        $userList = array();
        foreach ($model->find() as $row) {
            if (empty($row->id)) {
                throw new \Exception(
                    "Users model column 'id' cannot be empty."
                );
            }
            $userList[$row->id] = (object) array('id'=>$row->id, 'key'=>$row->key);
        }

        return $userList;
    }
}
