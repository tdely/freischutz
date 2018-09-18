<?php
namespace Reference\Controllers;

use Freischutz\Utility\Response;
use Phalcon\Mvc\Controller;
use Reference\Models\Example;

/**
 * Controller illustrating some techniques for working with a database.
 *
 * @see       https://gitlab.com/tdely/freischutz/ Freischutz on GitLab
 *
 * @author    Tobias Dély (tdely) <cleverhatcamouflage@gmail.com>
 * @copyright 2017 - present Tobias Dély
 * @license   https://directory.fsf.org/wiki/License:BSD-3-Clause BSD 3-clause "New" or "Revised" License
 */
class DatabaseController extends Controller
{
    /**
     * Retrieve all Example models, or a single one.
     *
     * This method retrieves all Example models as the default action; this
     * returns a 200 OK response even if no models exists, content body will be
     * an empty array. If the 'id' parameter is found as part of the URI
     * (NOT the query string), the model matching this ID will be retrieved;
     * if no model matching this ID is found, a 404 Not Found response is
     * returned.
     *
     * @return \Freischutz\Utility\Response
     */
    public function getAction()
    {
        $id = $this->dispatcher->getParam('id');

        $response = new Response();

        if (!$id) {
            // Get Phalcon result set, each row is built when it becomes required
            $result = Example::find();
            // Get all objects from result set (all rows become built)
            $data = $result->toArray();
            $response->ok($data);
        } else {
            if ($result = Example::findFirst($id)) {
                $response->ok($id);
            } else {
                $response->notFound();
            }
        }

        return $response;
    }

    /**
     * Retrieve Example models in chunks of 5, newest first.
     *
     * This method uses query string parameters to filter and control which
     * models are retrieved; without any query string parameters the 5 newest
     * are retrieved, it's then possible to get the next 5 newest by passing the
     * last model's id in the parameter 'continue'. The model also accepts
     * 'from' and 'to' in the query string for filtering through the models' 
     * timestamp field.
     *
     * @return \Freischutz\Utility\Response
     */
    public function filterGetAction()
    {
        if ($continue = $this->request->getQuery('continue')) {
            $conditions[] = "id<:id:";
            $bind['id'] = $continue;
        }
        if ($from = $this->request->getQuery('from')) {
            $conditions[] = "timestamp>=:from:";
            $bind['from'] = $from;
        }
        if ($to = $this->request->getQuery('to')) {
            $conditions[] = "timestamp<=:to:";
            $bind['to'] = $to;
        }
        $where = implode(' AND ', $conditions);

        $params = array(
            $where,
            'limit' => 5,
            'bind' => $bind,
            'order' => 'id DESC'
        );

        $response = new Response();
        // Get Phalcon result set, each row is built when it becomes required
        $result = Example::find($params);
        // Get all objects from result set (all rows become built)
        $data = $result->toArray();
        $response->ok($data);

        return $response;
    }

    /**
     * Create new Example model.
     *
     * This method takes the request data and creates a new Example model from
     * this data. If the model can be inserted into the database a 200 OK
     * response with the models data is returned, otherwise an error
     * response of a sensible type is returned with more information on what
     * went wrong.
     *
     * @return \Freischutz\Utility\Response
     */
    public function postAction()
    {
        $response = new Response();

        $example = new Example((array) $this->data->get());

        if ($example->create()) {
            $response->ok($example);
        } else {
            switch ($example->getMessages()[0]->getType()) {
                // Required field missing
                case 'PresenceOf':
                // Invalid value for field
                case 'InvalidValue':
                // Custom model validation on timestamp field failed
                case 'Date':
                    $response->unprocessableEntity(
                        implode("\n", $example->getMessages())
                    );
                    break;
                // A row already exists with the same identifier
                case 'InvalidCreateAttempt':
                    $response->conflict(implode("\n", $example->getMessages()));
                default:
                    $response->internalServerError('Oops, something went wrong.');
                    break;
            }
        }

        return $response;
    }
}

