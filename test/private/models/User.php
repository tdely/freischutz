<?php
namespace Test\Models;

use Phalcon\Mvc\Model;

/**
 * Model for Test
 */
class User extends Model
{
    public function initialize()
    {
        $this->setSource('User');
    }
}
