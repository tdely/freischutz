<?php
namespace Freischutz\Security;

use Phalcon\Mvc\User\Component;
use stdClass;

/**
 * Freischutz\Security\Basic
 *
 * Basic authentication.
 *
 * @see       https://gitlab.com/tdely/freischutz/ Freischutz on GitLab
 *
 * @author    Tobias Dély (tdely) <cleverhatcamouflage@gmail.com>
 * @copyright 2017-present Tobias Dély
 * @license   https://directory.fsf.org/wiki/License:BSD-3-Clause BSD 3-clause "New" or "Revised" License
 */
class Basic extends Component
{
    private $user;
    private $key;
    private $keyHashed;

    /**
     * Constructor.
     */
    public function __construct()
    {
        // Strip 'Basic ' from header
        $header = substr($this->request->getHeader('Authorization'), 6);

        $split = explode(':', base64_decode($header));

        $this->user = isset($split[0]) ? $split[0] : '';
        $this->key = isset($split[1]) ? $split[1] : '';
    }

    /**
     * Get user provided in request.
     *
     * @return string|int
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set hashed key.
     *
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

        if (password_verify($this->key, $this->keyHashed)) {
            $result->state = true;
            $this->logger->debug("[Basic] OK.");
        } else {
            $result->message = 'Password did not verify.';
            $this->logger->debug("[Basic] password did not verify.");
        }

        return $result;
    }
}
