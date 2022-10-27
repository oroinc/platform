<?php

namespace Oro\Bundle\IntegrationBundle\Test;

use GuzzleHttp\Utils;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\RestClientInterface;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\RestResponseInterface;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Exception\RestException;

/**
 * Fake REST client to not send real requests in tests
 */
class FakeRestClient implements RestClientInterface
{
    /** @var RestResponseInterface */
    protected $defaultFakeResponse;

    /** @var RestResponseInterface[] */
    protected $fakeResponseList = [];

    /** @var RestResponseInterface */
    protected $lastResponse;

    /**
     * {@inheritdoc}
     */
    public function get($resource, array $params = [], array $headers = [], array $options = [])
    {
        return $this->createFakeResponse($resource);
    }

    /**
     * {@inheritdoc}
     */
    public function getJSON($resource, array $params = [], array $headers = [], array $options = [])
    {
        $response = $this->get($resource, $params, $headers, $options);

        return Utils::jsonDecode($response->getBodyAsString(), true);
    }

    /**
     * {@inheritdoc}
     */
    public function post(
        $resource,
        $data,
        array $headers = [],
        array $options = []
    ) {
        return $this->createFakeResponse($resource);
    }

    /**
     * {@inheritdoc}
     */
    public function delete($resource, array $headers = [], array $options = [])
    {
        return $this->createFakeResponse($resource);
    }

    /**
     * {@inheritdoc}
     */
    public function put($resource, $data, array $headers = [], array $options = [])
    {
        return $this->createFakeResponse($resource);
    }

    /**
     * {@inheritdoc}
     */
    public function getLastResponse()
    {
        return $this->lastResponse;
    }

    /**
     * Allows set any response to fake client in case you need stub some data
     */
    public function setDefaultResponse(RestResponseInterface $fakeResponse)
    {
        $this->defaultFakeResponse = $fakeResponse;
    }

    /**
     * Allow setup several responses for different resources
     *
     * @param RestResponseInterface[] $responseList is an array where key is an resource url and value is response
     */
    public function setResponseList(array $responseList)
    {
        $this->fakeResponseList = $responseList;
    }

    /**
     * Creates fake response for all CRUD methods
     *
     * @param string $url
     *
     * @return RestResponseInterface
     */
    protected function createFakeResponse($url)
    {
        if (isset($this->fakeResponseList[$url])) {
            $fakeResponse = $this->fakeResponseList[$url];
        } else {
            $fakeResponse = $this->defaultFakeResponse;
        }

        $this->lastResponse = $fakeResponse;

        if ($fakeResponse->isError()) {
            throw RestException::createFromResponse($fakeResponse);
        }

        return $fakeResponse;
    }
}
