<?php
namespace Freischutz\Utility;

/**
 * Freischutz\Utility\Base64url
 *
 * Base64url functions.
 *
 * @see       https://gitlab.com/tdely/freischutz/ Freischutz on GitLab
 *
 * @author    Tobias Dély (tdely) <cleverhatcamouflage@gmail.com>
 * @copyright 2017-present Tobias Dély
 * @license   https://directory.fsf.org/wiki/License:BSD-3-Clause BSD 3-clause "New" or "Revised" License
 */
class Base64url
{
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
}
