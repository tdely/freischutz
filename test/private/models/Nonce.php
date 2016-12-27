<?php
namespace Test\Models;

use Phalcon\Mvc\Model;

/**
 * Model for Test
 */
class Nonce extends Model
{
    public function initialize()
    {
        $this->setSource('Nonce');
    }
}
