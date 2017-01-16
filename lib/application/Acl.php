<?php
namespace Freischutz\Application;

use Phalcon\Acl as PhalconAcl;
use Phalcon\Acl\Adapter\Memory as AclList;
use Phalcon\Acl\Resource;
use Phalcon\Acl\Role;
use Phalcon\Mvc\User\Component;

/**
 * Freischutz\Application\Acl
 */
class Acl extends Component
{
    private $acl;

    /**
     * Rebuild ACL.
     *
     * @return \Phalcon\Acl\Adapter\Memory
     */
    private function rebuild()
    {
        $doCache = in_array('acl', array_map(
            'trim',
            explode(',', $this->config->application->get('cache_parts', false))
        ));

        if ($this->di->has('cache') && $doCache) {
            if ($acl = $this->cache->get('_freischutz_acl')) {
                return $acl;
            }
        }

        /**
         * Build ACL
         */
        $backend = strtolower($this->config->acl->get('backend', 'file'));
        switch ($backend) {
            case 'file':
                $acl = $this->buildFromFiles();
                break;
            case 'database':
            case 'db':
                $acl = $this->buildFromDatabase();
                break;
            default:
                throw new \Exception("Unknown ACL backend: $backend");
        }

        $acl->setDefaultAction(PhalconAcl::DENY);

        if ($this->di->has('cache') && $doCache) {
            $this->cache->save('_freischutz_acl', $acl);
        }

        return $acl;
    }

    /**
     * Get ACL.
     *
     * @return \Phalcon\Acl\Adapter\Memory
     */
    private function getAcl()
    {
        if (is_object($this->acl)) {
            return $this->acl;
        }

        $this->acl = $this->rebuild();
        return $this->acl;
    }

    /**
     * Check if role is allowed to access resource.
     *
     * @param string $role Role requesting access.
     * @param string $controller Controller being targeted.
     * @param string $action Action being targeted.
     * @return bool
     */
    public function isAllowed($role, $controller, $action)
    {
        return $this->getAcl()->isAllowed($role, $controller, $action);
    }

    /**
     * Build ACL from file definitions.
     *
     * @throw \Exception if encountering a malformed line.
     * @throw \Exception if encountering a policy other than allow and deny.
     * @return \Phalcon\Acl\Adapter\Memory
     */
    private function buildFromFiles()
    {
        $acl = new AclList();

        $aclDir = $this->config->application->app_dir .
            $this->config->acl->dir;

        /**
         * Read role definitions
         */
        foreach (glob($aclDir . '/' . '*.roles') as $file) {
            $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                // Allow comments starting with '#', ';', or '//'
                if ($line[0] === '#' || $line[0] === ';'
                        || substr($line, 0, 2) === '//') {
                    continue;
                }
                $parts = explode(',', $line);
                if (sizeof($parts) === 1) {
                    $acl->addRole($parts[0]);
                } elseif (sizeof($parts) === 2) {
                    $acl->addRole(new Role ($parts[0], $parts[1]));
                } else {
                    throw new \Exception("Malformed row in $file: $line");
                }
            }
        }

        /**
         * Read role inheritance definitions
         */
        foreach (glob($aclDir . '/' . '*.inherits') as $file) {
            $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                // Allow comments starting with '#', ';', or '//'
                if ($line[0] === '#' || $line[0] === ';'
                        || substr($line, 0, 2) === '//') {
                    continue;
                }
                $parts = explode(',', $line);
                if (sizeof($parts) !== 2) {
                    throw new \Exception("Malformed row in $file: $line");
                }
                $acl->addInherit($parts[0], $parts[1]);
            }
        }

        /**
         * Read resource definitions
         */
        foreach (glob($aclDir . '/' . '*.resources') as $file) {
            $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                // Allow comments starting with '#', ';', or '//'
                if ($line[0] === '#' || $line[0] === ';'
                        || substr($line, 0, 2) === '//') {
                    continue;
                }
                $parts = explode(',', $line);
                if (sizeof($parts) !== 3) {
                    throw new \Exception("Malformed row in $file: $line");
                }
                $list = explode(';', $parts[2]);

                $acl->addResource(new Resource($parts[0], $parts[1]), $list);
            }
        }

        /**
         * Read rule definitions
         */
        foreach (glob($aclDir . '/' . '*.rules') as $file) {
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
                $policy = $parts[3];
                if ($policy !== 'allow' && $policy !== 'deny') {
                    throw new \Exception("Illegal policy in $file: $policy");
                }

                $acl->$policy($parts[0], $parts[1], $parts[2]);
            }
        }
        return $acl;
    }

    /**
     * Build ACL from database definitions.
     *
     * @throw \Exception if *_model not set in config acl section.
     * @throw \Exception if any model cannot be found.
     * @throw \Exception if any model is missing a required attribute.
     * @throw \Exception if encountering a policy other than allow and deny.
     * @return \Phalcon\Acl\Adapter\Memory
     */
    private function buildFromDatabase()
    {
        $acl = new AclList();

        $models = array(
            'role',
            'inherit',
            'resource',
            'rule'
        );

        // Throw exception if config options not set
        foreach ($models as $item) {
            $var = $item . '_model';
            if (!isset($this->config->acl->$var)) {
                throw new \Exception(
                    "ACL backend 'database' requires $var set in acl " .
                    "section in config file."
                );
            }
        }

        // Bind model names
        foreach ($models as $item) {
            $var = $item . '_model';
            ${$item . 'ModelName'} = $this->config->acl->$var;
            if (!class_exists(${$item . 'ModelName'})) {
                throw new \Exception("ACL " . $item . " model not found: " . ${$item . 'ModelName'});
            }
        }

        $roleModel = new $roleModelName;
        $inheritModel = new $inheritModelName;
        $resourceModel = new $resourceModelName;
        $ruleModel = new $ruleModelName;

        // Any model will do to get modelsMetaData object
        $metadata = $roleModel->getModelsMetaData();

        if (!$metadata->hasAttribute($roleModel, 'name')) {
            throw new \Exception(
                "Roles model must contain column 'name'."
            );
        } elseif (!$metadata->hasAttribute($inheritModel, 'role_name')
                || !$metadata->hasAttribute($inheritModel, 'inherit')) {
            throw new \Exception(
                "Inherits model must contain columns 'role_name' and 'inherit'."
            );
        } elseif (!$metadata->hasAttribute($resourceModel, 'controller')
                || !$metadata->hasAttribute($resourceModel, 'action')) {
            throw new \Exception(
                "Resources model must contain columns 'controller' and 'action'."
            );
        } elseif (!$metadata->hasAttribute($ruleModel, 'role_name')
                || !$metadata->hasAttribute($ruleModel, 'resource_controller')
                || !$metadata->hasAttribute($ruleModel, 'resource_action')
                || !$metadata->hasAttribute($ruleModel, 'policy')) {
            throw new \Exception(
                "Resources model must contain columns 'role_name', " .
                "'resource_controller', 'resource_action', and 'policy'."
            );
        }

        /**
         * Read role definitions
         */
        foreach ($roleModel->find() as $role) {
            $description = isset($role->description) ? $role->description : '';
            $acl->addRole(new Role($role->name, $description));
        }

        /**
         * Read role inheritance definitions
         */
        foreach ($inheritModel->find() as $inherit) {
            $acl->addInherit($inherit->role_name, $inherit->inherit);
        }

        /**
         * Read resource definitions
         */
        foreach ($resourceModel->find() as $resource) {
            $acl->addResource($resource->controller, $resource->action);
        }

        /**
         * Read rule definitions
         */
        foreach ($ruleModel->find() as $rule) {
            if ($rule->policy !== 'allow' && $rule->policy !== 'deny') {
                throw new \Exception("Illegal ACL policy: " . $rule->policy);
            }
            $policy = $rule->policy;
            $acl->$policy(
                $rule->role_name,
                $rule->resource_controller,
                $rule->resource_action
            );
        }

        return $acl;
    }
}
