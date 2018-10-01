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

        // Disassemble token
        $parts = explode('.', $token);

        if (count($parts) !== 3) {
            $this->logger->debug("[Jwt] Malformed token");
        } else {
            /**
             * Decode token pieces
             */
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
        if (empty($this->key)) {
            $this->logger->debug(
                "[Jwt] No key set for user ID {$this->payload->sub}"
            );
            $result->message = 'User denied.';
            return $result;
        }

        // Get grace time
        $grace = $this->config->jwt->get('grace', 0);
        if (!is_int($grace)) {
            $this->logger->warning("[Jwt] grace is not integer, using 0");
            $grace = 0;
        }

        // Get required claims and identify missing
        $requiredClaims = array();
        if ($this->config->jwt->get('claims', 'aud,iss')) {
            $requiredClaims = array_map(
                'trim',
                explode(',', $this->config->jwt->get('claims', 'aud,iss'))
            );
        }
        $missingClaims = array_diff(
            array_merge($requiredClaims, ['exp', 'iat']),
            array_keys((array) $this->payload)
        );

        // Evaluate audience if required
        $audienceOk = true;
        if ($this->config->jwt->get('claims', null)) {
            $audiences = array_map(
                'trim',
                explode(',', $this->config->jwt->get('aud', 'freischutz'))
            );
            $audienceOk = in_array($this->payload->aud, $audiences);
        }

        // Evaluate issuer if required
        $issuerOk = true;
        if ($this->config->jwt->get('claims', null)) {
            $issuers = array_map(
                'trim',
                explode(',', $this->config->jwt->get('iss', 'freischutz'))
            );
            $issuerOk = in_array($this->payload->aud, $issuers);
        }

        /**
         * Authenticate
         */
        $now = time();
        if (!$audienceOk) {
            $audString = implode(', ', $audiences);
            $result->message = "Token audience mismatch.";
            $this->logger->debug(
                "[Jwt] Token audience (aud) mismatch: expected (one of) " .
                "$audString; got {$this->payload->aud}."
            );
        } elseif (!$issuerOk) {
            $issString = implode(', ', $issuers);
            $result->message = "Token issuer mismatch.";
            $this->logger->debug(
                "[Jwt] Token issuer (iss) mismatch: expected (one of) " .
                "$issString; got {$this->payload->iss}."
            );
        } elseif (!$missingClaims) {
            // Required claims are present
            $notBefore = isset($this->payload->nbf)
                ? $this->payload->nbf - $grace
                : 0;
            $issuedAt = $this->payload->iat - $grace;

            if ($this->payload->exp + $grace > $now && $notBefore < $now
                    && $issuedAt < $now) {
                // Token is valid
                try {
                    $valid = JwtUtility::validate($this->token, $this->key);
                    if (!$valid) {
                        // Token signature is correct
                        $result->message = 'Signature invalid.';
                        $this->logger->debug(
                            "[Jwt] Failed to validate signature"
                        );
                    } else {
                        $result->state = true;
                        $this->logger->debug("[Jwt] OK.");
                    }
                } catch(Exception $e) {
                    // Token algorithm problem
                    $result->message = $e->getMessage();
                    $this->logger->debug("[Jwt] {$e->getMessage()}");
                }
            } elseif ($notBefore >= $now) {
                $timedelta = $notBefore - $now;
                $result->message = "Token not yet valid.";
                $this->logger->debug(
                    "[Jwt] Token nbf timedelta threshold exceeded: " .
                    "$timedelta (threshold ±$grace)."
                );
            } elseif ($issuedAt >= $now) {
                $timedelta = $issuedAt - $now;
                $result->message = "Token issued at a future time.";
                $this->logger->debug(
                    "[Jwt] Token iat timedelta threshold exceeded: " .
                    "$timedelta (threshold ±$grace)."
                );
            } else {
                $timedelta = $this->payload->exp - $now;
                $result->message = "Token expired.";
                $this->logger->debug(
                    "[Jwt] Token exp timedelta threshold exceeded: " .
                    "$timedelta (threshold ±$grace)."
                );
            }
        } else {
            $claims = join(', ', $missingClaims);
            $result->message = "Missing claims: $claims.";
            $this->logger->debug("[Jwt] Missing claims: $claims");
        }

        return $result;
    }
}
