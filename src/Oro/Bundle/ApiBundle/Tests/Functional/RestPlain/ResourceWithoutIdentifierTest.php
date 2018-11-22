<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\RestPlain;

use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Model\TestResourceWithoutIdentifier;
use Oro\Bundle\ApiBundle\Tests\Functional\RestPlainApiTestCase;
use Symfony\Component\HttpFoundation\Response;

class ResourceWithoutIdentifierTest extends RestPlainApiTestCase
{
    public function testGet()
    {
        $entityType = $this->getEntityType(TestResourceWithoutIdentifier::class);
        $response = $this->request(
            'GET',
            $this->getUrl($this->getListRouteName(), ['entity' => $entityType])
        );

        self::assertResponseStatusCodeEquals($response, Response::HTTP_OK);
        self::assertResponseContentTypeEquals($response, self::JSON_CONTENT_TYPE);
        $this->assertResponseContains(
            [
                'name' => 'test'
            ],
            $response
        );
    }

    public function testPost()
    {
        $data = [
            'name' => 'test'
        ];

        $entityType = $this->getEntityType(TestResourceWithoutIdentifier::class);
        $response = $this->request(
            'POST',
            $this->getUrl($this->getListRouteName(), ['entity' => $entityType]),
            $data
        );

        self::assertResponseStatusCodeEquals($response, Response::HTTP_CREATED);
        $this->assertResponseContains(
            [
                'name'        => 'test',
                'description' => null
            ],
            $response
        );
    }

    public function testPatch()
    {
        $data = [
            'name' => 'test'
        ];

        $entityType = $this->getEntityType(TestResourceWithoutIdentifier::class);
        $response = $this->request(
            'PATCH',
            $this->getUrl($this->getListRouteName(), ['entity' => $entityType]),
            $data
        );

        self::assertResponseStatusCodeEquals($response, Response::HTTP_OK);
        $this->assertResponseContains(
            [
                'name'        => 'test',
                'description' => null
            ],
            $response
        );
    }

    public function testDelete()
    {
        $entityType = $this->getEntityType(TestResourceWithoutIdentifier::class);
        $response = $this->request(
            'DELETE',
            $this->getUrl($this->getListRouteName(), ['entity' => $entityType])
        );

        self::assertResponseStatusCodeEquals($response, Response::HTTP_NO_CONTENT);
    }

    public function testValidationErrorPath()
    {
        $data = [
            'name' => ''
        ];

        $entityType = $this->getEntityType(TestResourceWithoutIdentifier::class);
        $response = $this->request(
            'POST',
            $this->getUrl($this->getListRouteName(), ['entity' => $entityType]),
            $data
        );

        $this->assertResponseValidationError(
            [
                'title'  => 'not blank constraint',
                'source' => 'name'
            ],
            $response
        );
    }

    public function testOptions()
    {
        $entityType = $this->getEntityType(TestResourceWithoutIdentifier::class);
        $response = $this->options($this->getListRouteName(), ['entity' => $entityType]);
        self::assertAllowResponseHeader($response, 'OPTIONS, GET, PATCH, POST, DELETE');
    }

    public function testNotAllowedMethodsItemHandler()
    {
        $this->appendEntityConfig(
            TestResourceWithoutIdentifier::class,
            [
                'actions' => [
                    'update' => false,
                    'delete' => false
                ]
            ],
            true
        );

        $this->assertNotAllowedMethod('PATCH', 'OPTIONS, GET, POST');
        $this->assertNotAllowedMethod('DELETE', 'OPTIONS, GET, POST');
        $this->assertNotAllowedMethod('HEAD', 'OPTIONS, GET, POST');
    }

    public function testNotAllowedMethodsListHandler()
    {
        $this->appendEntityConfig(
            TestResourceWithoutIdentifier::class,
            [
                'actions' => [
                    'create' => false,
                    'delete' => false
                ]
            ],
            true
        );

        $this->assertNotAllowedMethod('POST', 'OPTIONS, GET, PATCH');
        $this->assertNotAllowedMethod('DELETE', 'OPTIONS, GET, PATCH');
        $this->assertNotAllowedMethod('HEAD', 'OPTIONS, GET, PATCH');
    }

    public function testNotAllowedMethodsWhenGetActionIsExcluded()
    {
        $this->appendEntityConfig(
            TestResourceWithoutIdentifier::class,
            [
                'actions' => [
                    'get' => false
                ]
            ],
            true
        );

        $this->assertNotAllowedMethod('GET', 'OPTIONS, PATCH, POST, DELETE');
        $this->assertNotAllowedMethod('HEAD', 'OPTIONS, PATCH, POST, DELETE');
    }

    /**
     * @param string $method
     * @param string $expectedAllowedMethods
     */
    private function assertNotAllowedMethod($method, $expectedAllowedMethods)
    {
        $entityType = $this->getEntityType(TestResourceWithoutIdentifier::class);
        $response = $this->request(
            $method,
            $this->getUrl($this->getListRouteName(), ['entity' => $entityType])
        );
        self::assertMethodNotAllowedResponse($response, $expectedAllowedMethods, $method);
        self::assertResponseContentTypeEquals($response, self::JSON_CONTENT_TYPE, $method);
    }

    public function testGetWithStringCustomFilter()
    {
        $entityType = $this->getEntityType(TestResourceWithoutIdentifier::class);
        $response = $this->request(
            'GET',
            $this->getUrl($this->getListRouteName(), ['entity' => $entityType]),
            ['filter1' => 'filter value']
        );

        self::assertResponseStatusCodeEquals($response, Response::HTTP_OK);
        self::assertResponseContentTypeEquals($response, self::JSON_CONTENT_TYPE);
        $this->assertResponseContains(
            [
                'name' => 'test (filter1 value: filter value)'
            ],
            $response
        );
    }

    public function testGetWithTypedCustomFilterWithValidValue()
    {
        $entityType = $this->getEntityType(TestResourceWithoutIdentifier::class);
        $response = $this->request(
            'GET',
            $this->getUrl($this->getListRouteName(), ['entity' => $entityType]),
            ['filter2' => '2018-05-25']
        );

        self::assertResponseStatusCodeEquals($response, Response::HTTP_OK);
        self::assertResponseContentTypeEquals($response, self::JSON_CONTENT_TYPE);
        $this->assertResponseContains(
            [
                'name' => 'test (filter2 value: 25/5/2018)'
            ],
            $response
        );
    }

    public function testGetWithTypedCustomFilterWithInvalidValue()
    {
        $entityType = $this->getEntityType(TestResourceWithoutIdentifier::class);
        $response = $this->request(
            'GET',
            $this->getUrl($this->getListRouteName(), ['entity' => $entityType]),
            ['filter2' => 'test']
        );

        $this->assertResponseValidationError(
            [
                'title'  => 'filter constraint',
                'detail' => 'Expected date value. Given "test".',
                'source' => 'filter2'
            ],
            $response
        );
    }

    public function testGetWithUnknownFilter()
    {
        $entityType = $this->getEntityType(TestResourceWithoutIdentifier::class);
        $response = $this->request(
            'GET',
            $this->getUrl($this->getListRouteName(), ['entity' => $entityType]),
            ['anotherFilter' => 'filter value']
        );

        $this->assertResponseValidationError(
            [
                'title'  => 'filter constraint',
                'detail' => 'The filter is not supported.',
                'source' => 'anotherFilter'
            ],
            $response
        );
    }
}
