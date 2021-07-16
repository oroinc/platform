<?php

namespace Oro\Bundle\IntegrationBundle\Provider\Rest\Client\Guzzle;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Uri;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\RestClientInterface;

/**
 * Extended HTTP client based on Guzzle for simplifying REST API based integrations
 */
class GuzzleRestClient implements RestClientInterface
{
    /**
     * @var string
     */
    protected $baseUrl;

    /**
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
     * @var Request
     */
    protected $lastGuzzleRequest;

    public function __construct(string $baseUrl, array $defaultOptions = [])
    {
        $this->baseUrl = $baseUrl;
        $this->defaultOptions = $defaultOptions;
    }

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
    public function getJSON($resource, array $params = [], array $headers = [], array $options = [])
    {
        $response = $this->get($resource, $params, $headers, $options);
        if (!$response->isSuccessful()) {
            throw GuzzleRestException::createFromException(
                BadResponseException::create($this->lastGuzzleRequest, $response->getSourceResponse())
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
     * Add the base url to resource, if the resource is relative
     *
     * @param string $resource
     * @param array  $params
     * @return string
     */
    protected function buildUrl($resource, array $params): string
    {
        if (filter_var($resource, FILTER_VALIDATE_URL)) {
            $path = $resource;
        } else {
            $path = rtrim($this->baseUrl, '/') . '/' . ltrim($resource, '/');
        }

        $uri = new Uri($path);
        $uri = Uri::withQueryValues($uri, array_map('rawurlencode', $params));

        return (string)$uri;
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
        // Add the base url to resource, if the resource is relative
        $url = $this->buildUrl($url, $params);
        // Add default options to the options provided
        $options = array_merge($this->defaultOptions, $options);

        // set the "application/json" header if it was not set manually, in case data is an array
        if (is_array($data) && (!isset($headers['Content-Type']) || $headers['Content-Type']  == 'application/json')) {
            $headers['Content-Type'] = 'application/json';
            $data = json_encode($data);
        }

        try {
            $this->lastGuzzleRequest = $request = new Request(
                $method,
                $url,
                $headers,
                $data
            );
            $response = $this->getGuzzleClient()->send($request, $options);
        } catch (\Exception $exception) {
            throw GuzzleRestException::createFromException($exception);
        }

        $this->lastResponse = new GuzzleRestResponse($response, (string)$request->getUri());
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
