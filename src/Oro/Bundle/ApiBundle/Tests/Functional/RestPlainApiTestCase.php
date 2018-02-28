<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional;

use Oro\Bundle\ApiBundle\Request\RequestType;
use Symfony\Component\HttpFoundation\Response;

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
