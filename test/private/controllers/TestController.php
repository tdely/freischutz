<?php
namespace Test\Controllers;

use Phalcon\Mvc\Controller;
use Phalcon\Http\Response;

/**
 * Controller for Test
 */
class TestController extends Controller
{
    /**
     * Respond with 'Hello world!'
     */
    public function testAction()
    {
        $response = new Response();
        $response->setStatusCode(200);
        $response->setContentType('text/plain', 'utf-8');
        $response->setContent('Hello world!');
        return $response;
    }
}
