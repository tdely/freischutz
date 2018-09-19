<?php
namespace Freischutz\Validation;

use Phalcon\Validation;
use Phalcon\Validation\Message;
use Phalcon\Validation\Validator;

/**
 * Freischutz\Validation\Json
 *
 * JSON attribute validation.
 *
 * @see       https://gitlab.com/tdely/freischutz/ Freischutz on GitLab
 *
 * @author    Tobias Dély (tdely) <cleverhatcamouflage@gmail.com>
 * @copyright 2017-present Tobias Dély
 * @license   https://directory.fsf.org/wiki/License:BSD-3-Clause BSD 3-clause "New" or "Revised" License
 */
class Json extends Validator
{
    /**
     * Execute validation.
     *
     * @param Validation $validation
     * @param string $attribute
     *
     * @return bool
     */
    public function validate(Validation $validation, $attribute):bool
    {
        $value = $validation->getValue($attribute);

        if (empty($value) || json_decode($value)) {
            return true;
        }

        $error = json_last_error();
        $messages = array(
            JSON_ERROR_DEPTH => 'maximum stack depth exceeded',
            JSON_ERROR_STATE_MISMATCH => 'state mismatch',
            JSON_ERROR_CTRL_CHAR => 'unexpected control character',
            JSON_ERROR_SYNTAX => 'syntax error',
            JSON_ERROR_UTF8 => 'malformed UTF-8 characters'
        );

        $validation->appendMessage(new Message(
            "Invalid JSON in $attribute: {$messages[$error]}",
            $attribute,
            'JsonDecode'
        ));

        return false;
    }
}
