<?php
namespace Test\Models;

use Phalcon\Mvc\Model;

/**
 * Model for Test
 */
class Test extends Model
{
    public function initialize()
    {
        $this->setSource('Test');
    }
}
