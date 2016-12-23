<?php
namespace Test\Controllers;

use Freischutz\Utility\Response;
use Phalcon\Mvc\Controller;
use Test\Models\Test;

/**
 * Controller for Test
 */
class TestController extends Controller
{
    /**
     * Respond with 'Hello world!'
     */
    public function helloAction()
    {
        $response = new Response();
        $response->ok('Hello world!');
        return $response;
    }

    /**
     * XML test.
     */
    public function xmlAction()
    {
        $response = new Response();
        $data = $this->data->getXml();
        if ($data) {
            $response->ok($data);
        } else {
            $response->unprocessableEntity('Data malformed or missing');
        }
        return $response;
    }

    /**
     * JSON test.
     */
    public function jsonAction()
    {
        $response = new Response();
        $data = $this->data->getJson();
        if ($data) {
            $response->ok($data);
        } else {
            $response->unprocessableEntity('Data malformed or missing');
        }
        return $response;
    }

    /**
     * Get all rows from table.
     */
    public function getAction()
    {
        $response = new Response();
        $result = Test::find();
        $data = $result->toArray();
        $response->ok($data);
        return $response;
    }
}

