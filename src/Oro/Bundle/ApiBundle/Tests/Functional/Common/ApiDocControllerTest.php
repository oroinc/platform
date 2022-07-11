<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\Common;

use Oro\Bundle\ApiBundle\ApiDoc\RestDocUrlGenerator;
use Oro\Bundle\ApiBundle\Tests\Functional\ApiFeatureTrait;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * @group regression
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ApiDocControllerTest extends WebTestCase
{
    use ApiFeatureTrait;

    protected function setUp(): void
    {
        $this->initClient();
    }

    private function sendApiDocRequest(string $view = null): Response
    {
        $parameters = [];
        if (null !== $view) {
            $parameters['view'] = $view;
        }
        $this->client->request(
            'GET',
            $this->getUrl(RestDocUrlGenerator::ROUTE, $parameters)
        );

        return $this->client->getResponse();
    }

    private function sendApiDocResourceRequest(string $view, string $method, string $resource): Response
    {
        $resourceId = '/api/' . $resource;
        $backendPrefix = $this->getBackendPrefix();
        if ($backendPrefix) {
            $resourceId = $backendPrefix . $resourceId;
        }
        $resourceId = str_replace('/', '-', $resourceId);
        $resourceId = $method . '-' . $resourceId;

        $this->client->request(
            'GET',
            $this->getUrl(RestDocUrlGenerator::RESOURCE_ROUTE, ['view' => $view, 'resource' => $resourceId])
        );

        return $this->client->getResponse();
    }

    private function getBackendPrefix(): ?string
    {
        $container = self::getContainer();
        if (!$container->hasParameter('web_backend_prefix')) {
            return null;
        }

        $backendPrefix = $container->getParameter('web_backend_prefix');
        if ($backendPrefix) {
            $backendPrefix = rtrim($backendPrefix, '/');
        }

        return $backendPrefix;
    }

    public function testUnknownView()
    {
        $response = $this->sendApiDocRequest('unknown');
        self::assertResponseStatusCodeEquals($response, Response::HTTP_NOT_FOUND);
    }

    public function testDefaultView()
    {
        $response = $this->sendApiDocRequest();
        self::assertResponseStatusCodeEquals($response, Response::HTTP_OK);
    }

    public function testRestJsonApiView()
    {
        $response = $this->sendApiDocRequest('rest_json_api');
        self::assertResponseStatusCodeEquals($response, Response::HTTP_OK);
    }

    public function testRestPlainView()
    {
        $response = $this->sendApiDocRequest('rest_plain');
        self::assertResponseStatusCodeEquals($response, Response::HTTP_OK);
    }

    public function testRestJsonApiResource()
    {
        $response = $this->sendApiDocResourceRequest('rest_json_api', 'get', 'users');
        self::assertResponseStatusCodeEquals($response, Response::HTTP_OK);
    }

    public function testRestPlainResource()
    {
        $response = $this->sendApiDocResourceRequest('rest_plain', 'get', 'users');
        self::assertResponseStatusCodeEquals($response, Response::HTTP_OK);
    }

    public function testResourceForUnknownView()
    {
        $response = $this->sendApiDocResourceRequest('unknown', 'get', 'users');
        self::assertResponseStatusCodeEquals($response, Response::HTTP_NOT_FOUND);
    }

    public function testRestJsonApiUnknownResource()
    {
        $response = $this->sendApiDocResourceRequest('rest_json_api', 'get', 'unknown');
        self::assertResponseStatusCodeEquals($response, Response::HTTP_NOT_FOUND);
    }

    public function testViewOnDisabledFeature()
    {
        $this->disableApiFeature();
        try {
            $response = $this->sendApiDocRequest('rest_json_api');
        } finally {
            $this->enableApiFeature();
        }
        self::assertResponseStatusCodeEquals($response, Response::HTTP_NOT_FOUND);
    }

    public function testResourceOnDisabledFeature()
    {
        $this->disableApiFeature();
        try {
            $response = $this->sendApiDocResourceRequest('rest_json_api', 'get', 'users');
        } finally {
            $this->enableApiFeature();
        }
        self::assertResponseStatusCodeEquals($response, Response::HTTP_NOT_FOUND);
    }

    /**
     * @dataProvider notAllowedMethods
     */
    public function testNotAllowedMethod(string $method)
    {
        $this->client->request($method, $this->getUrl(RestDocUrlGenerator::ROUTE));
        $response = $this->client->getResponse();
        self::assertResponseStatusCodeEquals($response, Response::HTTP_METHOD_NOT_ALLOWED);
        self::assertAllowResponseHeader($response, 'GET');
    }

    /**
     * @dataProvider notAllowedMethods
     */
    public function testResourceNotAllowedMethod(string $method)
    {
        $this->client->request(
            $method,
            $this->getUrl(
                RestDocUrlGenerator::RESOURCE_ROUTE,
                ['view' => 'rest_json_api', 'resource' => 'test']
            )
        );
        $response = $this->client->getResponse();
        self::assertResponseStatusCodeEquals($response, Response::HTTP_METHOD_NOT_ALLOWED);
        self::assertAllowResponseHeader($response, 'GET');
    }

    /**
     * @dataProvider notAllowedMethods
     */
    public function testNotAllowedMethodForUnknownView(string $method)
    {
        $this->client->request($method, $this->getUrl(RestDocUrlGenerator::ROUTE, ['view' => 'unknown']));
        $response = $this->client->getResponse();
        self::assertResponseStatusCodeEquals($response, Response::HTTP_NOT_FOUND);
    }

    /**
     * @dataProvider notAllowedMethods
     */
    public function testResourceNotAllowedMethodForUnknownView(string $method)
    {
        $this->client->request(
            $method,
            $this->getUrl(
                RestDocUrlGenerator::RESOURCE_ROUTE,
                ['view' => 'unknown', 'resource' => 'test']
            )
        );
        $response = $this->client->getResponse();
        self::assertResponseStatusCodeEquals($response, Response::HTTP_NOT_FOUND);
    }

    /**
     * @dataProvider notAllowedMethods
     */
    public function testNotAllowedMethodOnDisabledFeature(string $method)
    {
        $this->disableApiFeature();
        try {
            $this->client->request($method, $this->getUrl(RestDocUrlGenerator::ROUTE));
            $response = $this->client->getResponse();
        } finally {
            $this->enableApiFeature();
        }
        self::assertResponseStatusCodeEquals($response, Response::HTTP_NOT_FOUND);
    }

    /**
     * @dataProvider notAllowedMethods
     */
    public function testResourceNotAllowedMethodOnDisabledFeature(string $method)
    {
        $this->disableApiFeature();
        try {
            $this->client->request(
                $method,
                $this->getUrl(
                    RestDocUrlGenerator::RESOURCE_ROUTE,
                    ['view' => 'rest_json_api', 'resource' => 'test']
                )
            );
            $response = $this->client->getResponse();
        } finally {
            $this->enableApiFeature();
        }
        self::assertResponseStatusCodeEquals($response, Response::HTTP_NOT_FOUND);
    }

    public function notAllowedMethods(): array
    {
        return [
            ['POST'],
            ['PATCH'],
            ['PUT'],
            ['DELETE'],
            ['OPTIONS'],
            ['HEAD'],
        ];
    }
}
