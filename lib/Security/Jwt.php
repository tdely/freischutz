<?php
namespace Freischutz\Security;

use Freischutz\Application\Exception;
use Freischutz\Utility\Base64url;
use Freischutz\Utility\Jwt as JwtUtility;
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
        if (!isset($this->config->jwt)) {
            throw new Exception(
                'JWT authentication requires jwt section in config file.'
            );
        }

        // Strip 'Bearer ' from authorization header
        $token = substr($this->request->getHeader('Authorization'), 7);

        $this->token = $token;
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            $this->logger->debug("[Jwt] Malformed token");
        } else {
            $header = $parts[0];
            $payload = $parts[1];
            $signature = $parts[2];
            if (!$this->header = json_decode(Base64url::decode($header))) {
                $this->logger->debug("[Jwt] Token header failed to decode");
            } elseif (!$this->payload = json_decode(Base64url::decode($payload))) {
                $this->logger->debug("[Jwt] Token payload failed to decode");
            } elseif (!$this->signature = Base64url::decode($signature)) {
                $this->logger->debug("[Jwt] Token signature failed to decode");
            }
        }

        $this->user = isset($this->payload->sub) ? $this->payload->sub : '';
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
            explode(',', $this->config->jwt->get('aud', 'freischutz'))
        );
        $allowedIssuers = array_map(
            'trim',
            explode(',', $this->config->jwt->get('iss', 'freischutz'))
        );

        $requiredClaims = array();
        if ($this->config->jwt->get('claims', null)) {
            $requiredClaims = array_map(
                'trim',
                explode(',', $this->config->jwt->get('claims', null))
            );
        }
        $missingClaims = array_diff(
            array_merge($requiredClaims, ['exp', 'iat']),
            array_keys((array) $this->payload)
        );

        if ($missingClaims) {
            $claims = join(', ', $missingClaims);
            $this->logger->debug("[Jwt] Missing claims: $claims");
            $result->message = "Missing claims: $claims.";
        } elseif ($this->payload->exp + $grace <= $time) {
            $this->logger->debug("[Jwt] Token expired (exp)");
            $result->message = "Token expired.";
        } elseif (isset($this->payload->nbf) && $this->payload->nbf - $grace > $time) {
            $this->logger->debug("[Jwt] Token not yet valid (nbf)");
            $result->message = "Token not yet valid.";
        } elseif (!empty($allowedAudiences) && (!isset($this->payload->aud)
                || !in_array($this->payload->aud, $allowedAudiences))) {
            $this->logger->debug("[Jwt] Token audience mismatch (aud)");
            $result->message = "Token audience mismatch.";
        } elseif (!empty($allowedIssuers) && (!isset($this->payload->iss)
                || !in_array($this->payload->iss, $allowedIssuers))) {
            $this->logger->debug("[Jwt] Token issuer mismatch (iss)");
            $result->message = "Token issuer mismatch.";
        } elseif (!empty($this->payload->iat) && $this->payload->iat >= $time) {
            $this->logger->debug("[Jwt] Token issued at a future time (iat)");
            $result->message = "Token issued at a future time.";
        } elseif (empty($this->key)) {
            $this->logger->debug(
                "[Jwt] No key set for user ID {$this->payload->sub}"
            );
            $result->message = 'User denied.';
        } elseif (!JwtUtility::validate($this->token, $this->key)) {
            $this->logger->debug(
                "[Jwt] Failed to validate signature"
            );
            $result->message = 'Signature invalid.';
        } else {
            $result->state = true;
        }

        return $result;
    }
}
