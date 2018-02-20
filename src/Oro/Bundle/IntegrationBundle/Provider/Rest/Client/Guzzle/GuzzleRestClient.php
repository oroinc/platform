<?php

namespace Oro\Bundle\IntegrationBundle\Provider\Rest\Client\Guzzle;

use Guzzle\Http\Client as GuzzleClient;
use Guzzle\Http\Exception\BadResponseException;
use Guzzle\Http\Message\RequestInterface;
use Guzzle\Http\Url as GuzzleUrl;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\RestClientInterface;

class GuzzleRestClient implements RestClientInterface
{
    /**
     * @var string
     */
    protected $baseUrl;

    /**
     * @see \Guzzle\Http\Message\RequestFactoryInterface::applyOptions
     * @var array
     */
    protected $defaultOptions;

    /**
     * @var GuzzleClient
     */
    protected $guzzleClient;

    /**
     * @var GuzzleRestResponse
     */
    protected $lastResponse;

    /**
     * @var RequestInterface
     */
    protected $lastGuzzleRequest;

    /**
     * @param string $baseUrl
     * @param array $defaultOptions
     */
    public function __construct($baseUrl, array $defaultOptions = [])
    {
        $this->baseUrl = $baseUrl;
        $this->defaultOptions = $defaultOptions;
    }

    /**
     * @param GuzzleClient $client
     */
    public function setGuzzleClient(GuzzleClient $client)
    {
        $this->guzzleClient = $client;
    }

    /**
     * @return GuzzleClient
     */
    protected function getGuzzleClient()
    {
        if (!$this->guzzleClient) {
            $this->guzzleClient = new GuzzleClient();
        }
        return $this->guzzleClient;
    }

    /**
     * {@inheritdoc}
     */
    public function get($resource, array $params = [], array $headers = [], array $options = [])
    {
        return $this->performRequest('get', $resource, $params, null, $headers, $options);
    }

    /**
     * {@inheritdoc}
     */
    public function getJSON($resource, array $params = array(), array $headers = array(), array $options = array())
    {
        $response = $this->get($resource, $params, $headers, $options);
        if (!$response->isSuccessful()) {
            throw GuzzleRestException::createFromException(
                BadResponseException::factory($this->lastGuzzleRequest, $response->getSourceResponse())
            );
        }

        return $response->json();
    }

    /**
     * {@inheritdoc}
     */
    public function post($resource, $data, array $headers = [], array $options = [])
    {
        return $this->performRequest('post', $resource, [], $data, $headers, $options);
    }

    /**
     * {@inheritdoc}
     */
    public function put($resource, $data, array $headers = [], array $options = [])
    {
        return $this->performRequest('put', $resource, [], $data, $headers, $options);
    }

    /**
     * {@inheritdoc}
     */
    public function delete($resource, array $headers = [], array $options = [])
    {
        return $this->performRequest('delete', $resource, [], null, $headers, $options);
    }

    /**
     * Build URL
     *
     * @param string $resource
     * @param array $params
     * @return string
     */
    protected function buildUrl($resource, array $params)
    {
        if (filter_var($resource, FILTER_VALIDATE_URL)) {
            $path = $resource;
        } else {
            $path = rtrim($this->baseUrl, '/') . '/' . ltrim($resource, '/');
        }

        $url = GuzzleUrl::factory($path);

        foreach ($params as $name => $value) {
            $url->getQuery()->add($name, $value);
        }

        return (string)$url;
    }

    /**
     * Performed request and return response
     *
     * @param string $method
     * @param string $url
     * @param array $params
     * @param mixed $data
     * @param array $headers
     * @param array $options
     * @return string
     * @throws GuzzleRestException
     */
    public function performRequest(
        $method,
        $url,
        array $params = [],
        $data = null,
        array $headers = [],
        array $options = []
    ) {
        $url = $this->buildUrl($url, $params);
        $options = array_merge($this->defaultOptions, $options);

        if (is_array($data) && (!isset($headers['Content-Type']) || $headers['Content-Type']  == 'application/json')) {
            $headers['Content-Type'] = 'application/json';
            $data = json_encode($data);
        }

        try {
            $this->lastGuzzleRequest = $request = $this->getGuzzleClient()->createRequest(
                $method,
                $url,
                $headers,
                $data,
                $options
            );
            $response = $request->send();
        } catch (\Exception $exception) {
            throw GuzzleRestException::createFromException($exception);
        }

        $this->lastResponse = new GuzzleRestResponse($response, (string)$request->getUrl());
        return $this->lastResponse;
    }

    /**
     * {@inheritdoc}
     */
    public function getLastResponse()
    {
        return $this->lastResponse;
    }
}
