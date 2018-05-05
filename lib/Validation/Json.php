<?php
namespace Freischutz\Validation;

use Phalcon\Validation;
use Phalcon\Validation\Message;
use Phalcon\Validation\Validator;

/**
 * JSON attribute validation.
 */
class Json extends Validator
{
    /**
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

        $validation->appendMessage(new Message(
            "$attribute contains invalid JSON",
            $attribute,
            'JsonDecode'
        ));

        return false;
    }
}
