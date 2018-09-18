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

        $validation->appendMessage(new Message(
            "$attribute contains invalid JSON",
            $attribute,
            'JsonDecode'
        ));

        return false;
    }
}
