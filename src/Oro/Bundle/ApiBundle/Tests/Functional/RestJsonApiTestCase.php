<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional;

use Doctrine\ORM\EntityManager;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

use Oro\Bundle\ApiBundle\Request\RequestType;

class RestJsonApiTestCase extends ApiTestCase
{
    const JSON_API_CONTENT_TYPE = 'application/vnd.api+json';

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient(
            [],
            array_replace(
                $this->generateWsseAuthHeader(),
                ['CONTENT_TYPE' => self::JSON_API_CONTENT_TYPE]
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
            ['CONTENT_TYPE' => self::JSON_API_CONTENT_TYPE]
        );

        return $this->client->getResponse();
    }

    /**
     * @return EntityManager
     */
    protected function getEntityManager()
    {
        return $this->getContainer()->get('doctrine')->getManager();
    }

    /**
     * @param ResponseHeaderBag $headers
     *
     * @return bool
     */
    protected static function isApplicableContentType(ResponseHeaderBag $headers)
    {
        return $headers->contains('Content-Type', self::JSON_API_CONTENT_TYPE);
    }
}
