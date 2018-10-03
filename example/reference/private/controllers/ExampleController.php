<?php
namespace Reference\Controllers;

use Freischutz\Utility\Response;
use Phalcon\Mvc\Controller;

/**
 * Controller illustrating some basic functionality, also usable for making
 * requests while testing configuration.
 *
 * @see       https://gitlab.com/tdely/freischutz/ Freischutz on GitLab
 *
 * @author    Tobias Dély (tdely) <cleverhatcamouflage@gmail.com>
 * @copyright 2017 - present Tobias Dély
 * @license   https://directory.fsf.org/wiki/License:BSD-3-Clause BSD-3-Clause
 */
class ExampleController extends Controller
{
    /**
     * Respond with 'Hello world!'
     *
     * @return \Freischutz\Utility\Response
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
     *
     * @return \Freischutz\Utility\Response
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
     *
     * @return \Freischutz\Utility\Response
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
     * Variable type example.
     *
     * Takes POST payload and handles it according to content type,
     * either attempting to decode it into some data type or using it raw.
     * If successful responds with 200 OK, sending the (possibly re-serialized)
     * data back as response payload. If the handler for the content type fails
     * it will respond with 400 Bad Request.
     *
     * @return \Freischutz\Utility\Response
     */
    public function varTypeAction()
    {
        $response = new Response();
        $data = $this->data->get();
        if ($data) {
            $response->ok($data);
        } else {
            $response->badRequest('Data malformed or missing');
        }
        return $response;
    }
}

