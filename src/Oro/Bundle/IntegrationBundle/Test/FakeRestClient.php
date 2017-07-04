<?php

namespace Oro\Bundle\IntegrationBundle\Test;

use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\RestClientInterface;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\RestResponseInterface;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Exception\RestException;

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
    public function get($resource, array $params = array(), array $headers = array(), array $options = array())
    {
        return $this->createFakeResponse($resource);
    }

    /**
     * {@inheritdoc}
     */
    public function getJSON($resource, array $params = array(), array $headers = array(), array $options = array())
    {
        return $this->get($resource, $params, $headers, $options)->json();
    }

    /**
     * {@inheritdoc}
     */
    public function post($resource, $data, array $headers = array(), array $options = array())
    {
        return $this->createFakeResponse($resource);
    }

    /**
     * {@inheritdoc}
     */
    public function delete($resource, array $headers = array(), array $options = array())
    {
        return $this->createFakeResponse($resource);
    }

    /**
     * {@inheritdoc}
     */
    public function put($resource, $data, array $headers = array(), array $options = array())
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
     *
     * @param RestResponseInterface $fakeResponse
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
