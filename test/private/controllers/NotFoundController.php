<?php
namespace Test\Controllers;

use Phalcon\Mvc\Controller;
use Phalcon\Http\Response;

/**
 * Controller for NotFound
 */
class NotFoundController extends Controller
{
    /**
     * Respond with not found
     */
    public function notFoundAction()
    {
        $response = new Response();
        $response->setStatusCode(404);
        $response->setContentType('text/plain', 'utf-8');
        $response->setContent('Resource not found.');
        return $response;
    }
}
