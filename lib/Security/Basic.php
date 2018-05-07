<?php
namespace Freischutz\Security;

use Freischutz\Application\Exception;
use Phalcon\Mvc\User\Component;

/**
 * Freischutz\Security\Basic
 *
 * Basic authentication.
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
     * @return mixed
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set hashed key.
     *
     * @return string
     */
    public function setKey($key)
    {
        $this->keyHashed = $key;
    }

    /**
     * Authenticate client request.
     *
     * @internal
     * @return object
     */
    public function authenticate()
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
