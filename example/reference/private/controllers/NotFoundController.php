<?php
namespace Reference\Controllers;

use Freischutz\Utility\Response;
use Phalcon\Mvc\Controller;

/**
 * Handle requests to unknown routes.
 *
 * @see       https://gitlab.com/tdely/freischutz/ Freischutz on GitLab
 *
 * @author    Tobias Dély (tdely) <cleverhatcamouflage@gmail.com>
 * @copyright 2017 - present Tobias Dély
 * @license   https://directory.fsf.org/wiki/License:BSD-3-Clause BSD 3-clause "New" or "Revised" License
 */
class NotFoundController extends Controller
{
    /**
     * Respond with 404 Not Found
     *
     * @return \Freischutz\Utility\Response
     */
    public function notFoundAction()
    {
        $response = new Response();
        $response->notFound('Resource not found.');
        return $response;
    }
}
