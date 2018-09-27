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

    private static $algorithms = array(
        'HS256' => 'sha256',
        'HS384' => 'sha384',
        'HS512' => 'sha512',
    );

    /**
     * Encode to base64url.
     *
     * @param string $input Data to base64url encode.
     * @return string
     */
    public static function base64url_encode(string $input):string
    {
        return rtrim(strtr(base64_encode($input), '+/', '-_'), '=');
    }

    /**
     * Decode from base64url.
     *
     * @param string $input Base64url string to decode.
     * @return string
     */
    public static function base64url_decode(string $input):string
    {
        $remainder = strlen($input)) % 4;
        $padding = $remainder ? str_repeat('=', 4 - $remainder) : '';
        return base64_decode(strtr($input, '-_', '+/') . $padding);
    }

    /**
     * Constructor.
     */
    public function __construct()
    {
        // Strip 'Bearer ' from authorization header
        $token = substr($this->request->getHeader('Authorization'), 8);

        $this->token = $token;
        $parts = split('.', $token);
        if (count($parts) !== 3) {
            $this->logger->debug("[Jwt] Malformed token");
        } else {
            $header = $parts[0];
            $payload = $parts[1];
            $signature = $parts[2];
            if (!$this->header = json_decode($this->base64url_decode($header))) {
                $this->logger->debug("[Jwt] Token header failed to decode");
            } elseif (!$this->payload = json_decode($this->base64url_decode($payload))) {
                $this->logger->debug("[Jwt] Token payload failed to decode");
            } elseif (!$this->signature = $this->base64url_decode($signature)) {
                $this->logger->debug("[Jwt] Token signature failed to decode");
            }
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
     * @param string $token JWT token to validate.
     * @param string $secret Secret used to sign JWT token.
     * @return bool
     */
    public static function validate(string $token, string $secret):bool
    {
        $parts = split('.', $token);
        if (count($parts) !== 3) {
            return false;
        }

        $header = json_decode(self::base64url_decode($parts[0]));
        $payload = json_decode(self::base64url_decode($parts[1]));
        $signature = $parts[2];

        if (!$header || ! $payload) {
            return false;
        }

        $algorithm = strtoupper(isset($payload->alg) ? $payload->alg : false);

        if (!$algorithm || !array_keys(self::algorithms, $algorithm, true)) {
            return false;
        }

        $hash = self::base64url_encode(hash_hmac(
            self::algorithms[$algorithm],
            substr($token, 0, strrpos($token,'.')),
            $secret,
            true
        ));
        if ($hash === $signature) {
            return true;
        }

        return false;
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
            split(',', $this->config->jwt->get('aud', 'freischutz'))
        );
        $allowedIssuers = array_map(
            'trim',
            split(',', $this->config->jwt->get('iss', 'freischutz'))
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
                || !in_array($this->payload->aud, $allowedAudiences))) {
            $this->logger->debug("[Jwt] Token audience mismatch");
            $result->message = "Token audience mismatch (aud).";
        } elseif (!empty($allowedIssuers) && (!isset($this->payload->iss)
                || !in_array($this->payload->iss, $allowedIssuers))) {
            $this->logger->debug("[Jwt] Token issuer mismatch");
            $result->message = "Token issuer mismatch (iss).";
        } elseif (empty($this->key)) {
            $this->logger->debug(
                "[Jwt] No key set for user ID {$this->payload->sub}"
            );
            $result->message = 'User denied.';
        } elseif (!$this->validate($this->token, $this->key)) {
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
