<?php
namespace Freischutz\Validation;

use Phalcon\Validation;
use Phalcon\Validation\Message;
use Phalcon\Validation\Validator;

/**
 * JSON attribute validation.
 *
 * @see       https://gitlab.com/tdely/freischutz/ Freischutz on GitLab
 *
 * @author    Tobias Dély (tdely) <cleverhatcamouflage@gmail.com>
 * @copyright 2017-present Tobias Dély
 * @license   https://directory.fsf.org/wiki/License:BSD-3-Clause BSD-3-Clause
 */
class Json extends Validator
{
    /**
     * Execute validation.
     *
     * @param Validation $validation
     * @param mixed $attribute
     * @return bool
     */
    public function validate(Validation $validation, $attribute):bool
    {
        $value = $validation->getValue($attribute);

        if (!is_string($value) && !empty($value)) {
            $validation->appendMessage(new Message(
                "Invalid JSON in $attribute: expected string, got " . gettype($value),
                $attribute,
                'JsonDecode'
            ));
            return false;
        }
        if (empty($value) || json_decode($value)) {
            return true;
        }

        $error = json_last_error_msg();

        $validation->appendMessage(new Message(
            "Invalid JSON in $attribute: $error",
            $attribute,
            'JsonDecode'
        ));

        return false;
    }
}
