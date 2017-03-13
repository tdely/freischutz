<?php
namespace Reference\Models;

use Phalcon\Mvc\Model;
use Phalcon\Validation;
use Phalcon\Validation\Validator\Date as DateValidator;

/**
 * Model for example table
 */
class Example extends Model
{
    /**
     * Data validation for model.
     *
     * @return bool
     */
    public function validation()
    {
        $validator = new Validation();

        /**
         * MySQL accepts invalid datetime values as zero value unless strict
         * mode is turned on, let's validate the timestamp field before
         * creating the model if it is set.
         */
        if (isset($this->timestamp)) {
            $validator->add('timestamp', new DateValidator(array(
                 'format' => 'Y-m-d H:i:s',
                 'message' => "timestamp must be valid datetime using format 'Y-m-d H:i:s'"
            )));
        }

        return $this->validate($validator);
    }
}
