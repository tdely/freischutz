<?php
namespace Freischutz\Application;

use Phalcon\Mvc\User\Component;

/**
 * Freischutz\Application\Data
 */
class Data extends Component
{
    private $data;

    /**
     * Constructor.
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Get data handled according to content-type.
     *
     * @return mixed
     */
    public function get()
    {
        switch ($this->request->getHeader('CONTENT_TYPE')) {
            case 'application/json':
                $data = $this->getJson();
                break;
            case 'application/xml':
            case 'text/xml':
                $data = $this->getXml();
                break;
            default:
                $data = $this->getRaw();
                break;
        }
        return $data;
    }

    /**
     * Get raw data.
     *
     * @return string|binary
     */
    public function getRaw()
    {
        return $this->data;
    }

    /**
     * Handle JSON data.
     *
     * @param bool $assoc (optional) Return associative array instead of object.
     * @return object|array|false
     */
    public function getJson($assoc = false)
    {
        $json = json_decode($this->data, $assoc);
        $json = $json !== null ? $json : false;
        return $json;
    }

    /**
     * Handle XML data.
     *
     * @return \SimpleXMLElement|false
     */
    public function getXml()
    {
        return simplexml_load_string($this->data);
    }
}
