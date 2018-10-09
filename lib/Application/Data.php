<?php
namespace Freischutz\Application;

use Phalcon\Mvc\User\Component;

/**
 * Freischutz data handling component.
 *
 * @see       https://gitlab.com/tdely/freischutz/ Freischutz on GitLab
 *
 * @author    Tobias Dély (tdely) <cleverhatcamouflage@gmail.com>
 * @copyright 2017-present Tobias Dély
 * @license   https://directory.fsf.org/wiki/License:BSD-3-Clause BSD-3-Clause
 */
class Data extends Component
{
    /** @var mixed Data. */
    private $data;

    /**
     * Data constructor.
     *
     * @param mixed $data
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
                $this->logger->debug('[Data] Accessing JSON data');
                $data = $this->getJson();
                break;
            case 'application/xml':
            case 'text/xml':
                $this->logger->debug('[Data] Accessing XML data');
                $data = $this->getXml();
                break;
            default:
                $this->logger->debug(
                    '[Data] Accessing raw data (type: ' .
                    $this->request->getHeader('CONTENT_TYPE') . ')'
                );
                $data = $this->getRaw();
                break;
        }
        return $data;
    }

    /**
     * Get raw data.
     *
     * @return mixed
     */
    public function getRaw()
    {
        return $this->data;
    }

    /**
     * Handle JSON data.
     *
     * @param bool $assoc (optional) Return associative array instead of object.
     * @return \stdClass|string[]|int[]|false
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
