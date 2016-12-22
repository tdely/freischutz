<?php
namespace Test\Controllers;

use Phalcon\Mvc\Controller;
use Phalcon\Http\Response;
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
