<?php
namespace Freischutz\Application;

use Phalcon\Mvc\User\Component;

/**
 * Freischutz\Application\Users
 */
class Users extends Component
{
    private $users;

    /**
     * Load user details.
     *
     * @return array
     */
    public function load()
    {
        switch ($backend = strtolower($this->config->application->get('users_backend', 'file'))) {
            case 'file':
                $users = $this->loadFromFiles();
                break;
            case 'config':
                $users = $this->loadFromConfig();
                break;
            case 'database':
            case 'db':
                //$this->user = $this->loadFromDatabase();
                throw new \Exception("Users backend 'database' not implemented");
                break;
            default:
                throw new \Exception("Unknown users backend: $backend");
        }

        return $users;
    }

    /**
     * Get user.
     *
     * @return object
     */
    public function getUser($id)
    {
        if (!is_object($this->users)) {
            $this->users = $this->load();
        }

        return $this->users[$id];
    }

    /**
     * Load user details from files.
     *
     * @return array
     */
    private function loadFromFiles()
    {
        $usersDir = $this->config->application->app_dir .
            $this->config->application->users_dir;

        /**
         * Read user definitions
         */
        foreach (glob($usersDir . '/' . '*.users') as $file) {
            $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                // Allow comments starting with '#', ';', or '//'
                if ($line[0] === '#' || $line[0] === ';'
                        || substr($line, 0, 2) === '//') {
                    continue;
                }
                $parts = explode(',', $line);
                // id,name,key
                if (sizeof($parts) !== 3) {
                    throw new \Exception("Malformed row in $file: $line");
                }
                $users[$parts[0]] = (object) array(
                    'id' => $parts[0],
                    'name' => $parts[1],
                    'key' => $parts[2]
                );
            };
        }

        return $users;
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

        $id=1;
        foreach ($this->config->users as $name => $key) {
            $users[$id] = (object) array('id' => $id, 'name' => $name, 'key' => $key);
            $id++;
        }

        return $users;
    }
}
