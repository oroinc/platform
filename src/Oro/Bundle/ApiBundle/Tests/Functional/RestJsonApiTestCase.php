<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional;

use Symfony\Component\HttpFoundation\Response;

use Oro\Bundle\ApiBundle\Request\RequestType;

class RestJsonApiTestCase extends ApiTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient(
            [],
            array_replace(
                $this->generateWsseAuthHeader(),
                ['CONTENT_TYPE' => 'application/vnd.api+json']
            )
        );

        parent::setUp();
    }

    /**
     * {@inheritdoc}
     */
    protected function getRequestType()
    {
        return new RequestType([RequestType::REST, RequestType::JSON_API]);
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
        $this->client->request(
            $method,
            $uri,
            $parameters,
            [],
            ['CONTENT_TYPE' => 'application/vnd.api+json']
        );

        return $this->client->getResponse();
    }
}
