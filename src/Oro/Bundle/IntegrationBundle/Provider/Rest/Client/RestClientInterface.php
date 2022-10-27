<?php

namespace Oro\Bundle\IntegrationBundle\Provider\Rest\Client;

use Oro\Bundle\IntegrationBundle\Provider\Rest\Exception\RestException;

/**
 * An interface for extended HTTP client based on Guzzle for simplifying REST API based integrations
 */
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
    public function get($resource, array $params = [], array $headers = [], array $options = []);

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
    public function getJSON($resource, array $params = [], array $headers = [], array $options = []);

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
    public function post($resource, $data, array $headers = [], array $options = []);

    /**
     * Send DELETE request
     *
     * @param string $resource Resource name or url
     * @param mixed $headers
     * @param mixed $options
     * @return RestResponseInterface
     * @throws RestException
     */
    public function delete($resource, array $headers = [], array $options = []);

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
    public function put($resource, $data, array $headers = [], array $options = []);

    /**
     * Get last response object
     *
     * @return RestResponseInterface|null
     */
    public function getLastResponse();
}
