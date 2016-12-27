<?php
namespace Freischutz\Event;

use Phalcon\Acl as PhalconAcl;
use Phalcon\Acl\Adapter\Memory as AclList;
use Phalcon\Acl\Resource;
use Phalcon\Acl\Role;
use Phalcon\Mvc\User\Component;

/**
 * Freischutz\Event\Acl
 */
class Acl extends Component
{
    private $acl;

    /**
     * Rebuild ACL.
     *
     * @return \Phalcon\Acl\Adapter\Memory
     */
    public function rebuild()
    {
        $acl = new AclList();
        $acl->setDefaultAction(PhalconAcl::DENY);

        /**
         * Build ACL
         */
        $acl = $this->buildFromFiles($acl);

        return $acl;
    }

    /**
     * Get ACL.
     *
     * @return \Phalcon\Acl\Adapter\Memory
     */
    public function getAcl()
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
     * @param \Phalcon\Acl\Adapter\Memory $acl ACL object to build.
     * @throw \Exception if encountering a malformed line.
     * @throw \Exception if encountering a policy other than allow and deny.
     * @return \Phalcon\Acl\Adapter\Memory
     */
    private function buildFromFiles($acl)
    {
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
            };
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
            };
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
            };
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
            };
        }
        return $acl;
    }
}
