<?php

namespace Oro\Bundle\IntegrationBundle\Provider\Rest\Client;

use Oro\Bundle\IntegrationBundle\Provider\Rest\Exception\RestException;

interface RestClientInterface
{
    /**
     * Send GET request
     *
     * @param string $resource Resource name or url
     * @param array $params Request parameters
     * @param mixed $headers
     * @param mixed $options
     * @return RestResponseInterface
     * @throws RestException
     */
    public function get($resource, array $params = array(), array $headers = array(), array $options = array());

    /**
     * Send GET request and return decoded JSON as array
     *
     * @param string $resource Resource name or url
     * @param array $params Request parameters
     * @param mixed $headers
     * @param mixed $options
     * @return array
     * @throws RestException
     */
    public function getJSON($resource, array $params = array(), array $headers = array(), array $options = array());

    /**
     * Send GET request and return \SimpleXMLElement object
     *
     * @param string $resource Resource name or url
     * @param array $params Request parameters
     * @param mixed $headers
     * @param mixed $options
     * @return \SimpleXMLElement
     * @throws RestException
     */
    public function getXML($resource, array $params = array(), array $headers = array(), array $options = array());

    /**
     * Send POST request
     *
     * @param string $resource Resource name or url
     * @param mixed $data Request body
     * @param mixed $headers
     * @param mixed $options
     * @return RestResponseInterface
     * @throws RestException
     */
    public function post($resource, $data, array $headers = array(), array $options = array());

    /**
     * Send DELETE request
     *
     * @param string $resource Resource name or url
     * @param mixed $headers
     * @param mixed $options
     * @return RestResponseInterface
     * @param mixed $headers
     * @param mixed $options
     * @throws RestException
     */
    public function delete($resource, array $headers = array(), array $options = array());

    /**
     * Send PUT request
     *
     * @param string $resource Resource name or url
     * @param mixed $data
     * @param mixed $headers
     * @param mixed $options
     * @return RestResponseInterface
     * @throws RestException
     */
    public function put($resource, $data, array $headers = array(), array $options = array());

    /**
     * Get last response object
     *
     * @return RestResponseInterface|null
     */
    public function getLastResponse();
}
