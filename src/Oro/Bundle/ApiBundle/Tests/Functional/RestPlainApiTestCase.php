<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional;

use Symfony\Component\HttpFoundation\Response;

use Oro\Bundle\ApiBundle\Request\RequestType;

class RestPlainApiTestCase extends ApiTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient([], $this->generateWsseAuthHeader());

        parent::setUp();
    }

    /**
     * {@inheritdoc}
     */
    protected function getRequestType()
    {
        return new RequestType([RequestType::REST]);
    }

    /**
     * @param string $method
     * @param string $uri
     * @param array  $parameters
     *
     * @return Response
     */
    protected function request($method, $uri, array $parameters = [])
    {
        $this->client->request($method, $uri, $parameters);

        return $this->client->getResponse();
    }
}
