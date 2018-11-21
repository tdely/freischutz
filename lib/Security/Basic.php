<?php
namespace Freischutz\Security;

use Freischutz\Application\Exception;
use Phalcon\Mvc\User\Component;
use stdClass;

/**
 * Basic authentication.
 *
 * @see       https://gitlab.com/tdely/freischutz/ Freischutz on GitLab
 *
 * @author    Tobias Dély (tdely) <cleverhatcamouflage@gmail.com>
 * @copyright 2017-present Tobias Dély
 * @license   https://directory.fsf.org/wiki/License:BSD-3-Clause BSD-3-Clause
 */
class Basic extends Component
{
    /** @var string User provided in request. */
    private $user;
    /** @var string Password provided in request. */
    private $key;
    /** @var string Hashed password to validate against. */
    private $keyHashed;

    /**
     * Basic constructor.
     */
    public function __construct()
    {
        // Strip 'Basic ' from header
        $header = substr($this->request->getHeader('Authorization'), 6);

        $split = explode(':', base64_decode($header));

        $this->user = $split[0] ?? '';
        $this->key = $split[1] ?? '';
    }

    /**
     * Get user provided in request.
     *
     * @internal
     * @return string
     */
    public function getUser():string
    {
        return $this->user;
    }

    /**
     * Set hashed password to validate against.
     *
     * @internal
     * @param string $key Hashed key
     * @return void
     */
    public function setKey(string $key)
    {
        $this->keyHashed = $key;
    }

    /**
     * Authenticate client request.
     *
     * @internal
     * @throws \Freischutz\Application\Exception
     * @return \stdClass
     */
    public function authenticate():stdClass
    {
        $result = (object) array('state' => false, 'message' => null);
        if (empty($this->key)) {
            $this->logger->debug(
                "[Basic] No hashed key set for user ID {$this->user}"
            );
            $result->message = 'User denied.';
            return $result;
        }

        if ($this->config->basic_auth->get('ldap', false)) {
            $address = $this->config->ldap->get('address', false);
            $port = $this->config->ldap->get('port', 389);
            if ($ldap = ldap_connect($address, $port)) {
                if ($this->config->ldap->get('version_3', false)) {
                    ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
                }
                $timeout = $this->config->ldap->get('timeout', 5);
                ldap_set_option($ldap, LDAP_OPT_NETWORK_TIMEOUT, $timeout);

                $attribute = $this->config->ldap->get('naming_attribute', 'cn');
                $path = $this->config->ldap->get('ldap_path', false);
                $dn = "$attribute={$this->user}" . ($path ? ",$path" : '');

                if (!ldap_bind($ldap, $dn, $this->key)) {
                    if (ldap_get_option($ldap, LDAP_OPT_ERROR_STRING, $error)) {
                        $this->logger->debug("[Basic] $error");
                    }
                    $result->message = 'LDAP authentication failed.';
                    $this->logger->debug("[Basic] LDAP authentication failed.");
                } else {
                    $result->state = true;
                    $this->logger->debug("[Basic] LDAP OK.");
                }
            } else {
                throw new Exception('Malformed address and/or port in LDAP connection');
            }
        } else {
            if (password_verify($this->key, $this->keyHashed)) {
                $result->state = true;
                $this->logger->debug("[Basic] OK.");
            } else {
                $result->message = 'Password did not verify.';
                $this->logger->debug("[Basic] password did not verify.");
            }
        }

        return $result;
    }
}
