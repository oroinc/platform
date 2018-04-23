<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional;

use Symfony\Component\HttpFoundation\Response;

use Oro\Bundle\ApiBundle\Request\RequestType;

/**
 * The base class for plain REST API functional tests.
 */
class RestPlainApiTestCase extends RestApiTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient();
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
     * Sends REST API request.
     *
     * @param string $method
     * @param string $uri
     * @param array  $parameters
     * @param array  $server
     *
     * @return Response
     */
    protected function request($method, $uri, array $parameters = [], array $server = [])
    {
        if (!isset($server['HTTP_X-WSSE'])) {
            $server = array_replace($server, $this->getWsseAuthHeader());
        }

        $this->client->request(
            $method,
            $uri,
            $parameters,
            [],
            $server
        );

        return $this->client->getResponse();
    }
}
