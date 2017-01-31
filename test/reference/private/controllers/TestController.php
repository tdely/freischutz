<?php
namespace Test\Controllers;

use Freischutz\Utility\Response;
use Phalcon\Mvc\Controller;
use Test\Models\Test;

/**
 * Controller illustrating some basic functionality, also usable for making
 * requests while testing configuration.
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
     *
     * Takes POST payload and attempts to turn it into a SimpleXMLElement
     * object. If successful responds with 200 OK, sending the re-serialized
     * object back as response payload. If failing to convert data into a
     * SimpleXMLElement it will respond with 400 Bad Request.
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
     *
     * Takes POST payload and attempts to decode it into a standard object
     * If successful responds with 200 OK, sending the re-serialized
     * object back as response payload. If json_decode fails to convert data
     * into an object it will respond with 400 Bad Request.
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
     * Database test.
     *
     * Retrieves all Test model records, which is all rows from the models
     * source table (test).
     */
    public function getAction()
    {
        $response = new Response();
        // Get Phalcon resultset, each row is built when it becomes required
        $result = Test::find();
        // Get all objects from resultset (all rows become built)
        $data = $result->toArray();
        $response->ok($data);
        return $response;
    }
}

