<?php
namespace Freischutz\Security;

use Freischutz\Application\Exception;
use Phalcon\Mvc\User\Component;
use stdClass;

/**
 * Freischutz\Security\Jwt
 *
 * JSON Web Token authentication.
 *
 * @see       https://gitlab.com/tdely/freischutz/ Freischutz on GitLab
 *
 * @author    Tobias Dély (tdely) <cleverhatcamouflage@gmail.com>
 * @copyright 2017-present Tobias Dély
 * @license   https://directory.fsf.org/wiki/License:BSD-3-Clause BSD 3-clause "New" or "Revised" License
 */
class Jwt extends Component
{
    private $user;
    private $key;

    private $token;
    private $header;
    private $payload;
    private $signature;

    /**
     * Constructor.
     */
    public function __construct()
    {
        // Strip 'Bearer ' from authorization header
        $token = substr($this->request->getHeader('Authorization'), 8);

        $parts = split('.', $token);
        if (count($parts) !== 3) {
            $this->logger->debug("[Jwt] Malformed token");
        }

        $this->token = $token;
        list($header, $payload, $signature) = $parts;
        if ($this->header = json_decode(base64_decode($header))) {
            $this->logger->debug("[Jwt] Token header failed to decode");
        } elseif ($this->payload = json_decode(base64_decode($payload))) {
            $this->logger->debug("[Jwt] Token payload failed to decode");
        } elseif ($this->signature = base64_decode($signature)) {
            $this->logger->debug("[Jwt] Token signature failed to decode");
        }

        $this->user = isset($payload->sub) ? $payload->sub : '';
    }

    /**
     * Get user (sub) provided in token payload.
     *
     * @return string|int
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set key.
     *
     * @return void
     */
    public function setKey(string $key)
    {
        $this->key = $key;
    }

    /**
     * Validate token signature.
     *
     * @return bool
     */
    private function validate():bool
    {
        $this->token;

        $algorithm = $this->payload->alg;

        $algorithms = array(
            'HS256' => 'sha256',
            'HS384' => 'sha384',
            'HS512' => 'sha512',
        );

        if (!array_keys($algorithms, $algorithm, true)) {
            // Algorithm not supported
        }

        $hash = hash_hmac($algorithms[$algorithm], $message, $this->key, true);
        if ($hash === $this->signature) {
            // Valid
        }
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
        
        $time = time();
        $grace = $this->config->jwt->get('grace', 0);
        if (!is_int($grace)) {
            $this->logger->warning("[Jwt] grace is not integer, using 0");
            $grace = 0;
        }

        $allowedAudiences = array_map(
            'trim',
            split(',', $this->config->jwt->get('aud', ''))
        );
        $allowedIssuers = array_map(
            'trim',
            split(',', $this->config->jwt->get('iss', ''))
        );
        $missing = array_diff(
            ['exp', 'iat', 'aud', 'iss', 'sub'],
            (array) $this->payload
        );

        if ($missing) {
            $claims = join(', ', $missing);
            $this->logger->debug("[Jwt] Missing claims: $claims");
            $result->message = "Missing claims: $claims.";
        } elseif ($this->payload->exp - $grace >= $time) {
            $this->logger->debug("[Jwt] Token expired");
            $result->message = "Token expired (exp).";
        } elseif (isset($this->payload->nbf) && $this->payload->nbf + $grace < $time) {
            $this->logger->debug("[Jwt] Token not yet valid");
            $result->message = "Token not yet valid (nbf).";
        } elseif (!empty($allowedAudiences) && (!isset($this->payload->aud)
                || in_array($this->payload->aud, $allowedAudiences))) {
            $this->logger->debug("[Jwt] Token audience mismatch");
            $result->message = "Token audience mismatch (aud).";
        } elseif (!empty($allowedIssuers) && (!isset($this->payload->iss)
                || in_array($this->payload->iss, $allowedIssuers))) {
            $this->logger->debug("[Jwt] Token issuer mismatch");
            $result->message = "Token issuer mismatch (iss).";
        } elseif (empty($this->key)) {
            $this->logger->debug(
                "[Jwt] No key set for user ID {$this->payload->sub}"
            );
            $result->message = 'User denied.';
        } else {
            $result->state = true;
        }


        return $result;
    }
}
