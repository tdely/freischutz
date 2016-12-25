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
     * Load user details.
     *
     * @return array
     */
    public function __construct()
    {
        switch ($backend = strtolower($this->config->application->get('users_backend', 'file'))) {
            case 'file':
                $userList = $this->loadFromFiles();
                break;
            case 'config':
                $userList = $this->loadFromConfig();
                break;
            case 'database':
            case 'db':
                //$this->user = $this->loadFromDatabase();
                throw new \Exception("Users backend 'database' not implemented");
                break;
            default:
                throw new \Exception("Unknown users backend: $backend");
        }

        $this->userList = (object) $userList;
    }

    /**
     * Set user.
     *
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
     * @return bool
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Load user details from files.
     *
     * @return array
     */
    private function loadFromFiles()
    {
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
            };
        }

        return $userList;
    }

    /**
     * Load user details from config.
     *
     * @return array
     */
    private function loadFromConfig()
    {
        if (!isset($this->config->users)) {
            throw new \Exception("Users backend 'config' requires section users in config file.");
        }

        foreach ($this->config->users as $id => $key) {
            $userList[$id] = (object) array('id' => $id, 'key' => $key);
        }

        return $userList;
    }
}
