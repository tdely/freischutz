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
     * Constructor
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Get raw data
     *
     * @return string|binary
     */
    public function getRaw()
    {
        return $this->data;
    }

    /**
     * Get JSON data
     *
     * @param bool $assoc (optional) Return associative array instead of object.
     * @return object|array
     */
    public function getJson($assoc = false)
    {
        return json_decode($this->data, $assoc);
    }

    /**
     * Get XML data
     *
     * @return \SimpleXMLElement
     */
    public function getXml()
    {
        return simplexml_load_string($this->data);
    }
}
