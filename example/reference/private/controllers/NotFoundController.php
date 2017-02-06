<?php
namespace Reference\Controllers;

use Freischutz\Utility\Response;
use Phalcon\Mvc\Controller;

/**
 * Handle requests to unknown routes.
 */
class NotFoundController extends Controller
{
    /**
     * Respond with 404 Not Found
     */
    public function notFoundAction()
    {
        $response = new Response();
        $response->notFound('Resource not found.');
        return $response;
    }
}
