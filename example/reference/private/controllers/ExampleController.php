<?php
namespace Reference\Controllers;

use Freischutz\Utility\Response;
use Phalcon\Mvc\Controller;
use Reference\Models\Example;

/**
 * Controller illustrating some basic functionality, also usable for making
 * requests while testing configuration.
 */
class ExampleController extends Controller
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
     * XML example.
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
            $response->badRequest('Data malformed or missing');
        }
        return $response;
    }

    /**
     * JSON example.
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
            $response->badRequest('Data malformed or missing');
        }
        return $response;
    }

    /**
     * Database example.
     *
     * Retrieves all Example model records, which is all rows from the models
     * source table (example).
     */
    public function getAction()
    {
        $response = new Response();
        // Get Phalcon resultset, each row is built when it becomes required
        $result = Example::find();
        // Get all objects from resultset (all rows become built)
        $data = $result->toArray();
        $response->ok($data);
        return $response;
    }
}

