<?php
namespace Test\Controllers;

use Phalcon\Http\Response;
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
        $response->setStatusCode(200);
        $response->setContentType('text/plain', 'utf-8');
        $response->setContent('Hello world!');
        return $response;
    }

    /*
     * XML test
     */
    public function xmlAction()
    {
        $response = new Response();
        $data = $this->data->getXml();
        $response->setStatusCode(200);
        $response->setContentType('application/xml', 'utf-8');
        $response->setContent($data->asXML());
        return $response;
    }

    /*
     * JSON test
     */
    public function jsonAction()
    {
        $response = new Response();
        $data = $this->data->getJson();
        $response->setStatusCode(200);
        $response->setContentType('application/json', 'utf-8');
        $response->setContent(json_encode($data));
        return $response;
    }

    /*
     * Get all rows from table
     */
    public function getAction()
    {
        $response = new Response();
        $result = Test::find();
        $responseData = $result->toArray();
        $response->setStatusCode(200);
        $response->setContentType('application/json', 'utf-8');
        $response->setContent(json_encode($responseData));
        return $response;
    }
}
