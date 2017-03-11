<?php
namespace Reference\Controllers;

use Freischutz\Utility\Response;
use Phalcon\Mvc\Controller;
use Reference\Models\Example;

/**
 * Controller illustrating some techniques for working with a database.
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
}

