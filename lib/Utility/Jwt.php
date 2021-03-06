<?php
namespace Freischutz\Utility;

use Freischutz\Application\Exception;
use stdClass;

/**
 * JSON Web Token functions.
 *
 * @see       https://gitlab.com/tdely/freischutz/ Freischutz on GitLab
 *
 * @author    Tobias Dély (tdely) <cleverhatcamouflage@gmail.com>
 * @copyright 2017-present Tobias Dély
 * @license   https://directory.fsf.org/wiki/License:BSD-3-Clause BSD-3-Clause
 */
class Jwt
{
    /**
     * Create signature from token.
     *
     * @throws \Freischutz\Application\Exception
     * @param string $algorithm Algorithm as defined in JWT standard.
     * @param string $token Token to derive signature from.
     * @param string $secret Secret used in cryptographic function.
     * @return string
     */
    public static function createSignature(string $algorithm, string $token, $secret):string
    {
        $type = substr($algorithm, 0, 2);
        $bits = substr($algorithm, 2);

        if (!preg_match('/^[0-9]+$/', $bits)
                || !in_array('sha' . $bits, hash_algos())) {
            throw new Exception("Unknown cryptographic algorithm: $algorithm");
        }

        switch ($type) {
            case 'HS':
                $hash = hash_hmac('sha' . $bits, $token, $secret, true);
                break;
            case 'RS':
            case 'PS':
                throw new Exception(
                    "Unimplemented cryptographic algorithm: $algorithm"
                );
                break;
            default:
                throw new Exception(
                    "Unknown cryptographic algorithm: $algorithm"
                );
                break;
        }

        return Base64url::encode($hash);
    }

    /**
     * Create JSON Web Token.
     *
     * @throws \Freischutz\Application\Exception
     * @param \stdClass $header JWT header data.
     * @param \stdClass $payload JWT payload data.
     * @param string $secret Secret used to sign JWT token.
     * @return string|false
     */
    public static function create(stdClass $header, stdClass $payload, string $secret)
    {
        $encodedHeader = Base64url::encode(json_encode($header));
        $encodedPayload = Base64url::encode(json_encode($payload));

        if (!isset($header->alg)) {
            throw new Exception("Token header missing 'alg'");
        }

        $signature = self::createSignature(
            $header->alg,
            "$encodedHeader.$encodedPayload",
            $secret
        );

        return $signature ? "$encodedHeader.$encodedPayload.$signature" : false;
    }

    /**
     * Validate token signature.
     *
     * @throws \Freischutz\Application\Exception
     * @param string $token JWT token to validate.
     * @param string $secret Secret used to sign JWT token.
     * @return bool
     */
    public static function validate(string $token, string $secret):bool
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return false;
        }

        $header = json_decode(Base64url::decode($parts[0]));
        $payload = json_decode(Base64url::decode($parts[1]));
        $signature = $parts[2];

        if (!$header || ! $payload) {
            return false;
        }

        $algorithm = strtoupper($header->alg ?? false);

        $createdSignature = self::createSignature(
            $algorithm,
            substr($token, 0, strrpos($token,'.')),
            $secret
        );
        if ($createdSignature === $signature) {
            return true;
        }

        return false;
    }
}
