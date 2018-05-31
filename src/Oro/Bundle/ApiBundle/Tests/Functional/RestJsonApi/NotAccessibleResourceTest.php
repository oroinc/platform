<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApi;

use Oro\Bundle\ApiBundle\Model\EntityIdentifier;
use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity\TestDepartment;
use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\TestFrameworkBundle\Entity\TestProduct;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class NotAccessibleResourceTest extends RestJsonApiTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->loadFixtures([
            '@OroApiBundle/Tests/Functional/DataFixtures/test_department.yml',
            '@OroApiBundle/Tests/Functional/DataFixtures/test_product.yml'
        ]);
    }

    /**
     * @param string $method
     * @param string $route
     * @param array  $routeParameters
     *
     * @dataProvider notAccessibleResourceActionsProvider
     */
    public function testNotAccessibleResource($method, $route, array $routeParameters = [])
    {
        $entityType = $this->getEntityType(EntityIdentifier::class);
        $response = $this->request(
            $method,
            $this->getUrl($route, array_merge($routeParameters, ['entity' => $entityType]))
        );

        self::assertResponseStatusCodeEquals($response, 404);
        self::assertResponseContentTypeEquals($response, self::JSON_API_CONTENT_TYPE);
        self::assertEquals(
            [
                'errors' => [
                    [
                        'status' => '404',
                        'title'  => 'resource not accessible exception',
                        'detail' => 'The resource is not accessible.'
                    ]
                ]
            ],
            self::jsonToArray($response->getContent())
        );
    }

    /**
     * @return array
     */
    public function notAccessibleResourceActionsProvider()
    {
        return [
            ['GET', 'oro_rest_api_item', ['id' => 123]],
            ['GET', 'oro_rest_api_list'],
            ['PATCH', 'oro_rest_api_item', ['id' => 123]],
            ['POST', 'oro_rest_api_list'],
            ['DELETE', 'oro_rest_api_item', ['id' => 123]],
            ['DELETE', 'oro_rest_api_list'],
            ['GET', 'oro_rest_api_subresource', ['id' => 123, 'association' => 'test']],
            ['GET', 'oro_rest_api_relationship', ['id' => 123, 'association' => 'test']],
            ['PATCH', 'oro_rest_api_relationship', ['id' => 123, 'association' => 'test']],
            ['POST', 'oro_rest_api_relationship', ['id' => 123, 'association' => 'test']],
            ['DELETE', 'oro_rest_api_relationship', ['id' => 123, 'association' => 'test']]
        ];
    }

    /**
     * @param string $method
     * @param string $route
     * @param array  $routeParameters
     *
     * @dataProvider unknownResourceActionsProvider
     */
    public function testUnknownResource($method, $route, array $routeParameters = [])
    {
        $entityType = 'unknown_entity';
        $response = $this->request(
            $method,
            $this->getUrl($route, array_merge($routeParameters, ['entity' => $entityType]))
        );

        self::assertResponseStatusCodeEquals($response, 404);
        self::assertResponseContentTypeEquals($response, self::JSON_API_CONTENT_TYPE);
        if ('HEAD' === $method) {
            // the HEAD response should not have the content
            self::assertEmpty($response->getContent());
        } else {
            $this->assertResponseContains(
                ['errors' => [['status' => '404', 'title' => 'entity type constraint']]],
                $response
            );
        }
    }

    /**
     * @return array
     */
    public function unknownResourceActionsProvider()
    {
        return [
            ['HEAD', 'oro_rest_api_item', ['id' => 123]],
            ['HEAD', 'oro_rest_api_list'],
            ['HEAD', 'oro_rest_api_subresource', ['id' => 123, 'association' => 'test']],
            ['HEAD', 'oro_rest_api_relationship', ['id' => 123, 'association' => 'test']],
            ['OPTIONS', 'oro_rest_api_item', ['id' => 123]],
            ['OPTIONS', 'oro_rest_api_list'],
            ['OPTIONS', 'oro_rest_api_subresource', ['id' => 123, 'association' => 'test']],
            ['OPTIONS', 'oro_rest_api_relationship', ['id' => 123, 'association' => 'test']],
            ['GET', 'oro_rest_api_item', ['id' => 123]],
            ['GET', 'oro_rest_api_list'],
            ['GET', 'oro_rest_api_subresource', ['id' => 123, 'association' => 'test']],
            ['GET', 'oro_rest_api_relationship', ['id' => 123, 'association' => 'test']],
            ['POST', 'oro_rest_api_item', ['id' => 123]],
            ['POST', 'oro_rest_api_list'],
            ['POST', 'oro_rest_api_subresource', ['id' => 123, 'association' => 'test']],
            ['POST', 'oro_rest_api_relationship', ['id' => 123, 'association' => 'test']],
            ['PATCH', 'oro_rest_api_item', ['id' => 123]],
            ['PATCH', 'oro_rest_api_list'],
            ['PATCH', 'oro_rest_api_subresource', ['id' => 123, 'association' => 'test']],
            ['PATCH', 'oro_rest_api_relationship', ['id' => 123, 'association' => 'test']],
            ['DELETE', 'oro_rest_api_item', ['id' => 123]],
            ['DELETE', 'oro_rest_api_list'],
            ['DELETE', 'oro_rest_api_subresource', ['id' => 123, 'association' => 'test']],
            ['DELETE', 'oro_rest_api_relationship', ['id' => 123, 'association' => 'test']]
        ];
    }

    public function testDisabledGet()
    {
        $this->appendEntityConfig(
            TestDepartment::class,
            ['actions' => ['get' => false]],
            true
        );

        $entityType = $this->getEntityType(TestDepartment::class);
        $response = $this->get(
            ['entity' => $entityType, 'id' => '@test_department->id'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, 404);
        self::assertResponseContentTypeEquals($response, self::JSON_API_CONTENT_TYPE);
        self::assertEquals(
            [
                'errors' => [
                    [
                        'status' => '404',
                        'title'  => 'resource not accessible exception',
                        'detail' => 'The resource is not accessible.'
                    ]
                ]
            ],
            self::jsonToArray($response->getContent())
        );
    }

    public function testDisabledGetList()
    {
        $this->appendEntityConfig(
            TestDepartment::class,
            ['actions' => ['get_list' => false]],
            true
        );

        $entityType = $this->getEntityType(TestDepartment::class);
        $response = $this->cget(
            ['entity' => $entityType],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, 405);
        self::assertResponseContentTypeEquals($response, self::JSON_API_CONTENT_TYPE);
        self::assertEquals('POST, DELETE', $response->headers->get('Allow'));
    }

    public function testDisabledGetListWhenGetIsDisabled()
    {
        $this->appendEntityConfig(
            TestDepartment::class,
            ['actions' => ['get_list' => false, 'get' => false]],
            true
        );

        $entityType = $this->getEntityType(TestDepartment::class);
        $response = $this->cget(
            ['entity' => $entityType],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, 404);
        self::assertResponseContentTypeEquals($response, self::JSON_API_CONTENT_TYPE);
        self::assertEquals(
            [
                'errors' => [
                    [
                        'status' => '404',
                        'title'  => 'resource not accessible exception',
                        'detail' => 'The resource is not accessible.'
                    ]
                ]
            ],
            self::jsonToArray($response->getContent())
        );
    }

    public function testDisabledCreate()
    {
        $this->appendEntityConfig(
            TestDepartment::class,
            ['actions' => ['create' => false]],
            true
        );

        $entityType = $this->getEntityType(TestDepartment::class);
        $response = $this->post(
            ['entity' => $entityType],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, 405);
        self::assertResponseContentTypeEquals($response, self::JSON_API_CONTENT_TYPE);
        self::assertEquals('GET, DELETE', $response->headers->get('Allow'));
    }

    public function testDisabledCreateWhenGetIsDisabled()
    {
        $this->appendEntityConfig(
            TestDepartment::class,
            ['actions' => ['create' => false, 'get' => false]],
            true
        );

        $entityType = $this->getEntityType(TestDepartment::class);
        $response = $this->post(
            ['entity' => $entityType],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, 404);
        self::assertResponseContentTypeEquals($response, self::JSON_API_CONTENT_TYPE);
        self::assertEquals(
            [
                'errors' => [
                    [
                        'status' => '404',
                        'title'  => 'resource not accessible exception',
                        'detail' => 'The resource is not accessible.'
                    ]
                ]
            ],
            self::jsonToArray($response->getContent())
        );
    }

    public function testDisabledUpdate()
    {
        $this->appendEntityConfig(
            TestDepartment::class,
            ['actions' => ['update' => false]],
            true
        );

        $entityType = $this->getEntityType(TestDepartment::class);
        $response = $this->patch(
            ['entity' => $entityType, 'id' => '@test_department->id'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, 405);
        self::assertResponseContentTypeEquals($response, self::JSON_API_CONTENT_TYPE);
        self::assertEquals('GET, DELETE', $response->headers->get('Allow'));
    }

    public function testDisabledUpdateWhenGetIsDisabled()
    {
        $this->appendEntityConfig(
            TestDepartment::class,
            ['actions' => ['update' => false, 'get' => false]],
            true
        );

        $entityType = $this->getEntityType(TestDepartment::class);
        $response = $this->patch(
            ['entity' => $entityType, 'id' => '@test_department->id'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, 404);
        self::assertResponseContentTypeEquals($response, self::JSON_API_CONTENT_TYPE);
        self::assertEquals(
            [
                'errors' => [
                    [
                        'status' => '404',
                        'title'  => 'resource not accessible exception',
                        'detail' => 'The resource is not accessible.'
                    ]
                ]
            ],
            self::jsonToArray($response->getContent())
        );
    }

    public function testDisabledDelete()
    {
        $this->appendEntityConfig(
            TestDepartment::class,
            ['actions' => ['delete' => false]],
            true
        );

        $entityType = $this->getEntityType(TestDepartment::class);
        $response = $this->delete(
            ['entity' => $entityType, 'id' => '@test_department->id'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, 405);
        self::assertResponseContentTypeEquals($response, self::JSON_API_CONTENT_TYPE);
        self::assertEquals('GET, PATCH', $response->headers->get('Allow'));
    }

    public function testDisabledDeleteWhenGetIsDisabled()
    {
        $this->appendEntityConfig(
            TestDepartment::class,
            ['actions' => ['delete' => false, 'get' => false]],
            true
        );

        $entityType = $this->getEntityType(TestDepartment::class);
        $response = $this->delete(
            ['entity' => $entityType, 'id' => '@test_department->id'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, 404);
        self::assertResponseContentTypeEquals($response, self::JSON_API_CONTENT_TYPE);
        self::assertEquals(
            [
                'errors' => [
                    [
                        'status' => '404',
                        'title'  => 'resource not accessible exception',
                        'detail' => 'The resource is not accessible.'
                    ]
                ]
            ],
            self::jsonToArray($response->getContent())
        );
    }

    public function testDisabledDeleteList()
    {
        $this->appendEntityConfig(
            TestDepartment::class,
            ['actions' => ['delete_list' => false]],
            true
        );

        $entityType = $this->getEntityType(TestDepartment::class);
        $response = $this->cdelete(
            ['entity' => $entityType],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, 405);
        self::assertResponseContentTypeEquals($response, self::JSON_API_CONTENT_TYPE);
        self::assertEquals('GET, POST', $response->headers->get('Allow'));
    }

    public function testDisabledDeleteListWhenGetIsDisabled()
    {
        $this->appendEntityConfig(
            TestDepartment::class,
            ['actions' => ['delete_list' => false, 'get' => false]],
            true
        );

        $entityType = $this->getEntityType(TestDepartment::class);
        $response = $this->cdelete(
            ['entity' => $entityType],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, 404);
        self::assertResponseContentTypeEquals($response, self::JSON_API_CONTENT_TYPE);
        self::assertEquals(
            [
                'errors' => [
                    [
                        'status' => '404',
                        'title'  => 'resource not accessible exception',
                        'detail' => 'The resource is not accessible.'
                    ]
                ]
            ],
            self::jsonToArray($response->getContent())
        );
    }

    public function testDisabledGetSubresource()
    {
        $this->appendEntityConfig(
            TestDepartment::class,
            ['subresources' => ['owner' => ['actions' => ['get_subresource' => false]]]],
            true
        );

        $entityType = $this->getEntityType(TestDepartment::class);
        $response = $this->getSubresource(
            ['entity' => $entityType, 'id' => '@test_department->id', 'association' => 'owner'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, 404);
        self::assertResponseContentTypeEquals($response, self::JSON_API_CONTENT_TYPE);
    }

    public function testDisabledGetSubresourceWhenAllSubresourcesAreDisabled()
    {
        $this->appendEntityConfig(
            TestDepartment::class,
            ['actions' => ['get_subresource' => false]],
            true
        );

        $entityType = $this->getEntityType(TestDepartment::class);
        $response = $this->getSubresource(
            ['entity' => $entityType, 'id' => '@test_department->id', 'association' => 'owner'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, 404);
        self::assertResponseContentTypeEquals($response, self::JSON_API_CONTENT_TYPE);
        self::assertEquals(
            [
                'errors' => [
                    [
                        'status' => '404',
                        'title'  => 'not found http exception',
                        'detail' => 'Unsupported subresource.'
                    ]
                ]
            ],
            self::jsonToArray($response->getContent())
        );
    }

    public function testDisabledGetSubresourceWhenGetIsDisabled()
    {
        $this->appendEntityConfig(
            TestDepartment::class,
            ['actions' => ['get_subresource' => false, 'get' => false]],
            true
        );

        $entityType = $this->getEntityType(TestDepartment::class);
        $response = $this->getSubresource(
            ['entity' => $entityType, 'id' => '@test_department->id', 'association' => 'owner'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, 404);
        self::assertResponseContentTypeEquals($response, self::JSON_API_CONTENT_TYPE);
        self::assertEquals(
            [
                'errors' => [
                    [
                        'status' => '404',
                        'title'  => 'resource not accessible exception',
                        'detail' => 'The resource is not accessible.'
                    ]
                ]
            ],
            self::jsonToArray($response->getContent())
        );
    }

    public function testDisabledGetRelationship()
    {
        $this->appendEntityConfig(
            TestDepartment::class,
            ['subresources' => ['owner' => ['actions' => ['get_relationship' => false]]]],
            true
        );

        $entityType = $this->getEntityType(TestDepartment::class);
        $response = $this->getRelationship(
            ['entity' => $entityType, 'id' => '@test_department->id', 'association' => 'owner'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, 405);
        self::assertResponseContentTypeEquals($response, self::JSON_API_CONTENT_TYPE);
        self::assertEquals('PATCH', $response->headers->get('Allow'));
    }

    public function testDisabledGetRelationshipWhenAllGetRelationshipsAreDisabled()
    {
        $this->appendEntityConfig(
            TestDepartment::class,
            ['actions' => ['get_relationship' => false]],
            true
        );

        $entityType = $this->getEntityType(TestDepartment::class);
        $response = $this->getRelationship(
            ['entity' => $entityType, 'id' => '@test_department->id', 'association' => 'owner'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, 405);
        self::assertResponseContentTypeEquals($response, self::JSON_API_CONTENT_TYPE);
        self::assertEquals('PATCH', $response->headers->get('Allow'));
    }

    public function testDisabledGetRelationshipWhenAllSubresourcesAreDisabled()
    {
        $this->appendEntityConfig(
            TestDepartment::class,
            ['actions' => ['get_subresource' => false]],
            true
        );

        $entityType = $this->getEntityType(TestDepartment::class);
        $response = $this->getRelationship(
            ['entity' => $entityType, 'id' => '@test_department->id', 'association' => 'owner'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, 404);
        self::assertResponseContentTypeEquals($response, self::JSON_API_CONTENT_TYPE);
        self::assertEquals(
            [
                'errors' => [
                    [
                        'status' => '404',
                        'title'  => 'not found http exception',
                        'detail' => 'Unsupported subresource.'
                    ]
                ]
            ],
            self::jsonToArray($response->getContent())
        );
    }

    public function testDisabledGetRelationshipWhenGetIsDisabled()
    {
        $this->appendEntityConfig(
            TestDepartment::class,
            ['actions' => ['get_relationship' => false, 'get' => false]],
            true
        );

        $entityType = $this->getEntityType(TestDepartment::class);
        $response = $this->getRelationship(
            ['entity' => $entityType, 'id' => '@test_department->id', 'association' => 'owner'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, 404);
        self::assertResponseContentTypeEquals($response, self::JSON_API_CONTENT_TYPE);
        self::assertEquals(
            [
                'errors' => [
                    [
                        'status' => '404',
                        'title'  => 'resource not accessible exception',
                        'detail' => 'The resource is not accessible.'
                    ]
                ]
            ],
            self::jsonToArray($response->getContent())
        );
    }

    public function testDisabledUpdateRelationship()
    {
        $this->appendEntityConfig(
            TestDepartment::class,
            ['subresources' => ['owner' => ['actions' => ['update_relationship' => false]]]],
            true
        );

        $entityType = $this->getEntityType(TestDepartment::class);
        $response = $this->patchRelationship(
            ['entity' => $entityType, 'id' => '@test_department->id', 'association' => 'owner'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, 405);
        self::assertResponseContentTypeEquals($response, self::JSON_API_CONTENT_TYPE);
        self::assertEquals('GET', $response->headers->get('Allow'));
    }

    public function testDisabledUpdateRelationshipWhenAllUpdateRelationshipsAreDisabled()
    {
        $this->appendEntityConfig(
            TestDepartment::class,
            ['actions' => ['update_relationship' => false]],
            true
        );

        $entityType = $this->getEntityType(TestDepartment::class);
        $response = $this->patchRelationship(
            ['entity' => $entityType, 'id' => '@test_department->id', 'association' => 'owner'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, 405);
        self::assertResponseContentTypeEquals($response, self::JSON_API_CONTENT_TYPE);
        self::assertEquals('GET', $response->headers->get('Allow'));
    }

    public function testDisabledUpdateRelationshipWhenAllSubresourcesAreDisabled()
    {
        $this->appendEntityConfig(
            TestDepartment::class,
            ['actions' => ['get_subresource' => false]],
            true
        );

        $entityType = $this->getEntityType(TestDepartment::class);
        $response = $this->patchRelationship(
            ['entity' => $entityType, 'id' => '@test_department->id', 'association' => 'owner'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, 404);
        self::assertResponseContentTypeEquals($response, self::JSON_API_CONTENT_TYPE);
        self::assertEquals(
            [
                'errors' => [
                    [
                        'status' => '404',
                        'title'  => 'not found http exception',
                        'detail' => 'Unsupported subresource.'
                    ]
                ]
            ],
            self::jsonToArray($response->getContent())
        );
    }

    public function testDisabledUpdateRelationshipWhenGetIsDisabled()
    {
        $this->appendEntityConfig(
            TestDepartment::class,
            ['actions' => ['update_relationship' => false, 'get' => false]],
            true
        );

        $entityType = $this->getEntityType(TestDepartment::class);
        $response = $this->patchRelationship(
            ['entity' => $entityType, 'id' => '@test_department->id', 'association' => 'owner'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, 404);
        self::assertResponseContentTypeEquals($response, self::JSON_API_CONTENT_TYPE);
        self::assertEquals(
            [
                'errors' => [
                    [
                        'status' => '404',
                        'title'  => 'resource not accessible exception',
                        'detail' => 'The resource is not accessible.'
                    ]
                ]
            ],
            self::jsonToArray($response->getContent())
        );
    }

    public function testDisabledAddRelationship()
    {
        $this->appendEntityConfig(
            TestDepartment::class,
            ['subresources' => ['staff' => ['actions' => ['add_relationship' => false]]]],
            true
        );

        $entityType = $this->getEntityType(TestDepartment::class);
        $response = $this->postRelationship(
            ['entity' => $entityType, 'id' => '@test_department->id', 'association' => 'staff'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, 405);
        self::assertResponseContentTypeEquals($response, self::JSON_API_CONTENT_TYPE);
        self::assertEquals('GET, PATCH, DELETE', $response->headers->get('Allow'));
    }

    public function testDisabledAddRelationshipWhenAllAddRelationshipsAreDisabled()
    {
        $this->appendEntityConfig(
            TestDepartment::class,
            ['actions' => ['add_relationship' => false]],
            true
        );

        $entityType = $this->getEntityType(TestDepartment::class);
        $response = $this->postRelationship(
            ['entity' => $entityType, 'id' => '@test_department->id', 'association' => 'staff'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, 405);
        self::assertResponseContentTypeEquals($response, self::JSON_API_CONTENT_TYPE);
        self::assertEquals('GET, PATCH, DELETE', $response->headers->get('Allow'));
    }

    public function testDisabledAddRelationshipWhenAllSubresourcesAreDisabled()
    {
        $this->appendEntityConfig(
            TestDepartment::class,
            ['actions' => ['get_subresource' => false]],
            true
        );

        $entityType = $this->getEntityType(TestDepartment::class);
        $response = $this->postRelationship(
            ['entity' => $entityType, 'id' => '@test_department->id', 'association' => 'staff'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, 404);
        self::assertResponseContentTypeEquals($response, self::JSON_API_CONTENT_TYPE);
        self::assertEquals(
            [
                'errors' => [
                    [
                        'status' => '404',
                        'title'  => 'not found http exception',
                        'detail' => 'Unsupported subresource.'
                    ]
                ]
            ],
            self::jsonToArray($response->getContent())
        );
    }

    public function testDisabledAddRelationshipWhenGetIsDisabled()
    {
        $this->appendEntityConfig(
            TestDepartment::class,
            ['actions' => ['add_relationship' => false, 'get' => false]],
            true
        );

        $entityType = $this->getEntityType(TestDepartment::class);
        $response = $this->postRelationship(
            ['entity' => $entityType, 'id' => '@test_department->id', 'association' => 'staff'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, 404);
        self::assertResponseContentTypeEquals($response, self::JSON_API_CONTENT_TYPE);
        self::assertEquals(
            [
                'errors' => [
                    [
                        'status' => '404',
                        'title'  => 'resource not accessible exception',
                        'detail' => 'The resource is not accessible.'
                    ]
                ]
            ],
            self::jsonToArray($response->getContent())
        );
    }

    public function testDisabledDeleteRelationship()
    {
        $this->appendEntityConfig(
            TestDepartment::class,
            ['subresources' => ['staff' => ['actions' => ['delete_relationship' => false]]]],
            true
        );

        $entityType = $this->getEntityType(TestDepartment::class);
        $response = $this->deleteRelationship(
            ['entity' => $entityType, 'id' => '@test_department->id', 'association' => 'staff'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, 405);
        self::assertResponseContentTypeEquals($response, self::JSON_API_CONTENT_TYPE);
        self::assertEquals('GET, PATCH, POST', $response->headers->get('Allow'));
    }

    public function testDisabledDeleteRelationshipWhenAllAddRelationshipsAreDisabled()
    {
        $this->appendEntityConfig(
            TestDepartment::class,
            ['actions' => ['delete_relationship' => false]],
            true
        );

        $entityType = $this->getEntityType(TestDepartment::class);
        $response = $this->deleteRelationship(
            ['entity' => $entityType, 'id' => '@test_department->id', 'association' => 'staff'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, 405);
        self::assertResponseContentTypeEquals($response, self::JSON_API_CONTENT_TYPE);
        self::assertEquals('GET, PATCH, POST', $response->headers->get('Allow'));
    }

    public function testDisabledDeleteRelationshipWhenAllSubresourcesAreDisabled()
    {
        $this->appendEntityConfig(
            TestDepartment::class,
            ['actions' => ['get_subresource' => false]],
            true
        );

        $entityType = $this->getEntityType(TestDepartment::class);
        $response = $this->deleteRelationship(
            ['entity' => $entityType, 'id' => '@test_department->id', 'association' => 'staff'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, 404);
        self::assertResponseContentTypeEquals($response, self::JSON_API_CONTENT_TYPE);
        self::assertEquals(
            [
                'errors' => [
                    [
                        'status' => '404',
                        'title'  => 'not found http exception',
                        'detail' => 'Unsupported subresource.'
                    ]
                ]
            ],
            self::jsonToArray($response->getContent())
        );
    }

    public function testDisabledDeleteRelationshipWhenGetIsDisabled()
    {
        $this->appendEntityConfig(
            TestDepartment::class,
            ['actions' => ['delete_relationship' => false, 'get' => false]],
            true
        );

        $entityType = $this->getEntityType(TestDepartment::class);
        $response = $this->deleteRelationship(
            ['entity' => $entityType, 'id' => '@test_department->id', 'association' => 'staff'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, 404);
        self::assertResponseContentTypeEquals($response, self::JSON_API_CONTENT_TYPE);
        self::assertEquals(
            [
                'errors' => [
                    [
                        'status' => '404',
                        'title'  => 'resource not accessible exception',
                        'detail' => 'The resource is not accessible.'
                    ]
                ]
            ],
            self::jsonToArray($response->getContent())
        );
    }

    public function testDisabledAllRelationshipActions()
    {
        $this->appendEntityConfig(
            TestDepartment::class,
            [
                'actions' => [
                    'get_relationship'    => false,
                    'update_relationship' => false,
                    'add_relationship'    => false,
                    'delete_relationship' => false
                ]
            ],
            true
        );

        $entityType = $this->getEntityType(TestDepartment::class);
        $response = $this->patchRelationship(
            ['entity' => $entityType, 'id' => '@test_department->id', 'association' => 'staff'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, 404);
        self::assertResponseContentTypeEquals($response, self::JSON_API_CONTENT_TYPE);
    }

    public function testDisabledAllRelationshipActionsForToOneAssociation()
    {
        $this->appendEntityConfig(
            TestDepartment::class,
            [
                'actions' => [
                    'get_relationship'    => false,
                    'update_relationship' => false
                ]
            ],
            true
        );

        $entityType = $this->getEntityType(TestDepartment::class);
        $response = $this->patchRelationship(
            ['entity' => $entityType, 'id' => '@test_department->id', 'association' => 'owner'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, 404);
        self::assertResponseContentTypeEquals($response, self::JSON_API_CONTENT_TYPE);
    }

    public function testAddRelationshipForToOneAssociation()
    {
        $entityType = $this->getEntityType(TestDepartment::class);
        $response = $this->postRelationship(
            ['entity' => $entityType, 'id' => '@test_department->id', 'association' => 'owner'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, 405);
        self::assertResponseContentTypeEquals($response, self::JSON_API_CONTENT_TYPE);
        self::assertEquals('GET, PATCH', $response->headers->get('Allow'));
    }

    public function testDeleteRelationshipToOneAssociation()
    {
        $entityType = $this->getEntityType(TestDepartment::class);
        $response = $this->deleteRelationship(
            ['entity' => $entityType, 'id' => '@test_department->id', 'association' => 'owner'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, 405);
        self::assertResponseContentTypeEquals($response, self::JSON_API_CONTENT_TYPE);
        self::assertEquals('GET, PATCH', $response->headers->get('Allow'));
    }

    public function testPostMethodForSingleItemPrimaryResourceRoute()
    {
        $entityType = $this->getEntityType(TestDepartment::class);
        $response = $this->request(
            'POST',
            $this->getUrl(
                'oro_rest_api_item',
                self::processTemplateData(['entity' => $entityType, 'id' => '@test_department->id'])
            )
        );
        self::assertResponseStatusCodeEquals($response, 405);
        self::assertResponseContentTypeEquals($response, self::JSON_API_CONTENT_TYPE);
        self::assertEquals('GET, PATCH, DELETE', $response->headers->get('Allow'));
    }

    public function testOptionsMethodForSingleItemPrimaryResourceRoute()
    {
        $entityType = $this->getEntityType(TestDepartment::class);
        $response = $this->request(
            'OPTIONS',
            $this->getUrl(
                'oro_rest_api_item',
                self::processTemplateData(['entity' => $entityType, 'id' => '@test_department->id'])
            )
        );
        self::assertResponseStatusCodeEquals($response, 405);
        self::assertResponseContentTypeEquals($response, self::JSON_API_CONTENT_TYPE);
        self::assertEquals('GET, PATCH, DELETE', $response->headers->get('Allow'));
    }

    public function testHeadMethodForSingleItemPrimaryResourceRoute()
    {
        $entityType = $this->getEntityType(TestDepartment::class);
        $response = $this->request(
            'HEAD',
            $this->getUrl(
                'oro_rest_api_item',
                self::processTemplateData(['entity' => $entityType, 'id' => '@test_department->id'])
            )
        );
        self::assertResponseStatusCodeEquals($response, 405);
        self::assertResponseContentTypeEquals($response, self::JSON_API_CONTENT_TYPE);
        self::assertEquals('GET, PATCH, DELETE', $response->headers->get('Allow'));
    }

    public function testPatchMethodForListPrimaryResourceRoute()
    {
        $entityType = $this->getEntityType(TestDepartment::class);
        $response = $this->request(
            'PATCH',
            $this->getUrl('oro_rest_api_list', ['entity' => $entityType])
        );
        self::assertResponseStatusCodeEquals($response, 405);
        self::assertResponseContentTypeEquals($response, self::JSON_API_CONTENT_TYPE);
        self::assertEquals('GET, POST, DELETE', $response->headers->get('Allow'));
    }

    public function testOptionsMethodForListPrimaryResourceRoute()
    {
        $entityType = $this->getEntityType(TestDepartment::class);
        $response = $this->request(
            'OPTIONS',
            $this->getUrl('oro_rest_api_list', ['entity' => $entityType])
        );
        self::assertResponseStatusCodeEquals($response, 405);
        self::assertResponseContentTypeEquals($response, self::JSON_API_CONTENT_TYPE);
        self::assertEquals('GET, POST, DELETE', $response->headers->get('Allow'));
    }

    public function testHeadMethodForListPrimaryResourceRoute()
    {
        $entityType = $this->getEntityType(TestDepartment::class);
        $response = $this->request(
            'HEAD',
            $this->getUrl('oro_rest_api_list', ['entity' => $entityType])
        );
        self::assertResponseStatusCodeEquals($response, 405);
        self::assertResponseContentTypeEquals($response, self::JSON_API_CONTENT_TYPE);
        self::assertEquals('GET, POST, DELETE', $response->headers->get('Allow'));
    }

    public function testOptionsMethodForRelationshipRoute()
    {
        $entityType = $this->getEntityType(TestDepartment::class);
        $response = $this->request(
            'OPTIONS',
            $this->getUrl(
                'oro_rest_api_relationship',
                self::processTemplateData([
                    'entity'      => $entityType,
                    'id'          => '@test_department->id',
                    'association' => 'staff'
                ])
            )
        );
        self::assertResponseStatusCodeEquals($response, 405);
        self::assertResponseContentTypeEquals($response, self::JSON_API_CONTENT_TYPE);
        self::assertEquals('GET, PATCH, POST, DELETE', $response->headers->get('Allow'));
    }

    public function testHeadMethodForRelationshipRoute()
    {
        $entityType = $this->getEntityType(TestDepartment::class);
        $response = $this->request(
            'HEAD',
            $this->getUrl(
                'oro_rest_api_relationship',
                self::processTemplateData([
                    'entity'      => $entityType,
                    'id'          => '@test_department->id',
                    'association' => 'staff'
                ])
            )
        );
        self::assertResponseStatusCodeEquals($response, 405);
        self::assertResponseContentTypeEquals($response, self::JSON_API_CONTENT_TYPE);
        self::assertEquals('GET, PATCH, POST, DELETE', $response->headers->get('Allow'));
    }

    public function testPostMethodForSubresourceRoute()
    {
        $entityType = $this->getEntityType(TestDepartment::class);
        $response = $this->postSubresource(
            ['entity' => $entityType, 'id' => '@test_department->id', 'association' => 'staff'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, 405);
        self::assertResponseContentTypeEquals($response, self::JSON_API_CONTENT_TYPE);
        self::assertEquals('GET', $response->headers->get('Allow'));
    }

    public function testPatchMethodForSubresourceRoute()
    {
        $entityType = $this->getEntityType(TestDepartment::class);
        $response = $this->patchSubresource(
            ['entity' => $entityType, 'id' => '@test_department->id', 'association' => 'staff'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, 405);
        self::assertResponseContentTypeEquals($response, self::JSON_API_CONTENT_TYPE);
        self::assertEquals('GET', $response->headers->get('Allow'));
    }

    public function testDeleteMethodForSubresourceRoute()
    {
        $entityType = $this->getEntityType(TestDepartment::class);
        $response = $this->deleteSubresource(
            ['entity' => $entityType, 'id' => '@test_department->id', 'association' => 'staff'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, 405);
        self::assertResponseContentTypeEquals($response, self::JSON_API_CONTENT_TYPE);
        self::assertEquals('GET', $response->headers->get('Allow'));
    }

    public function testOptionsMethodForSubresourceRoute()
    {
        $entityType = $this->getEntityType(TestDepartment::class);
        $response = $this->request(
            'OPTIONS',
            $this->getUrl(
                'oro_rest_api_subresource',
                self::processTemplateData([
                    'entity'      => $entityType,
                    'id'          => '@test_department->id',
                    'association' => 'staff'
                ])
            )
        );
        self::assertResponseStatusCodeEquals($response, 405);
        self::assertResponseContentTypeEquals($response, self::JSON_API_CONTENT_TYPE);
        self::assertEquals('GET', $response->headers->get('Allow'));
    }

    public function testHeadMethodForSubresourceRoute()
    {
        $entityType = $this->getEntityType(TestDepartment::class);
        $response = $this->request(
            'HEAD',
            $this->getUrl(
                'oro_rest_api_subresource',
                self::processTemplateData([
                    'entity'      => $entityType,
                    'id'          => '@test_department->id',
                    'association' => 'staff'
                ])
            )
        );
        self::assertResponseStatusCodeEquals($response, 405);
        self::assertResponseContentTypeEquals($response, self::JSON_API_CONTENT_TYPE);
        self::assertEquals('GET', $response->headers->get('Allow'));
    }

    public function testGetSubresourceWithUnacessibleTarget()
    {
        $entityType = $this->getEntityType(TestProduct::class);
        $response = $this->getSubresource(
            ['entity' => $entityType, 'id' => '@test_product->id', 'association' => 'unaccessible-target'],
            [],
            [],
            false
        );

        self::assertResponseStatusCodeEquals($response, 404);
        self::assertResponseContentTypeEquals($response, self::JSON_API_CONTENT_TYPE);
        self::assertEquals(
            [
                'errors' => [
                    [
                        'status' => '404',
                        'title'  => 'not found http exception',
                        'detail' => 'Unsupported subresource.'
                    ]
                ]
            ],
            self::jsonToArray($response->getContent())
        );
    }

    public function testPostSubresourceWithUnacessibleTarget()
    {
        $entityType = $this->getEntityType(TestProduct::class);
        $response = $this->postSubresource(
            ['entity' => $entityType, 'id' => '@test_product->id', 'association' => 'unaccessible-target'],
            [],
            [],
            false
        );

        self::assertResponseStatusCodeEquals($response, 404);
        self::assertResponseContentTypeEquals($response, self::JSON_API_CONTENT_TYPE);
        self::assertEquals(
            [
                'errors' => [
                    [
                        'status' => '404',
                        'title'  => 'not found http exception',
                        'detail' => 'Unsupported subresource.'
                    ]
                ]
            ],
            self::jsonToArray($response->getContent())
        );
    }

    public function testPatchSubresourceWithUnacessibleTarget()
    {
        $entityType = $this->getEntityType(TestProduct::class);
        $response = $this->patchSubresource(
            ['entity' => $entityType, 'id' => '@test_product->id', 'association' => 'unaccessible-target'],
            [],
            [],
            false
        );

        self::assertResponseStatusCodeEquals($response, 404);
        self::assertResponseContentTypeEquals($response, self::JSON_API_CONTENT_TYPE);
        self::assertEquals(
            [
                'errors' => [
                    [
                        'status' => '404',
                        'title'  => 'not found http exception',
                        'detail' => 'Unsupported subresource.'
                    ]
                ]
            ],
            self::jsonToArray($response->getContent())
        );
    }

    public function testDeleteSubresourceWithUnacessibleTarget()
    {
        $entityType = $this->getEntityType(TestProduct::class);
        $response = $this->deleteSubresource(
            ['entity' => $entityType, 'id' => '@test_product->id', 'association' => 'unaccessible-target'],
            [],
            [],
            false
        );

        self::assertResponseStatusCodeEquals($response, 404);
        self::assertResponseContentTypeEquals($response, self::JSON_API_CONTENT_TYPE);
        self::assertEquals(
            [
                'errors' => [
                    [
                        'status' => '404',
                        'title'  => 'not found http exception',
                        'detail' => 'Unsupported subresource.'
                    ]
                ]
            ],
            self::jsonToArray($response->getContent())
        );
    }

    public function testGetRelationshipWithUnacessibleTarget()
    {
        $entityType = $this->getEntityType(TestProduct::class);
        $response = $this->getRelationship(
            ['entity' => $entityType, 'id' => '@test_product->id', 'association' => 'unaccessible-target'],
            [],
            [],
            false
        );

        self::assertResponseStatusCodeEquals($response, 404);
        self::assertResponseContentTypeEquals($response, self::JSON_API_CONTENT_TYPE);
        self::assertEquals(
            [
                'errors' => [
                    [
                        'status' => '404',
                        'title'  => 'not found http exception',
                        'detail' => 'Unsupported subresource.'
                    ]
                ]
            ],
            self::jsonToArray($response->getContent())
        );
    }

    public function testPostRelationshipWithUnacessibleTarget()
    {
        $entityType = $this->getEntityType(TestProduct::class);
        $response = $this->postRelationship(
            ['entity' => $entityType, 'id' => '@test_product->id', 'association' => 'unaccessible-target'],
            [],
            [],
            false
        );

        self::assertResponseStatusCodeEquals($response, 404);
        self::assertResponseContentTypeEquals($response, self::JSON_API_CONTENT_TYPE);
        self::assertEquals(
            [
                'errors' => [
                    [
                        'status' => '404',
                        'title'  => 'not found http exception',
                        'detail' => 'Unsupported subresource.'
                    ]
                ]
            ],
            self::jsonToArray($response->getContent())
        );
    }

    public function testPatchRelationshipWithUnacessibleTarget()
    {
        $entityType = $this->getEntityType(TestProduct::class);
        $response = $this->patchRelationship(
            ['entity' => $entityType, 'id' => '@test_product->id', 'association' => 'unaccessible-target'],
            [],
            [],
            false
        );

        self::assertResponseStatusCodeEquals($response, 404);
        self::assertResponseContentTypeEquals($response, self::JSON_API_CONTENT_TYPE);
        self::assertEquals(
            [
                'errors' => [
                    [
                        'status' => '404',
                        'title'  => 'not found http exception',
                        'detail' => 'Unsupported subresource.'
                    ]
                ]
            ],
            self::jsonToArray($response->getContent())
        );
    }

    public function testDeleteRelationshipWithUnacessibleTarget()
    {
        $entityType = $this->getEntityType(TestProduct::class);
        $response = $this->deleteRelationship(
            ['entity' => $entityType, 'id' => '@test_product->id', 'association' => 'unaccessible-target'],
            [],
            [],
            false
        );

        self::assertResponseStatusCodeEquals($response, 404);
        self::assertResponseContentTypeEquals($response, self::JSON_API_CONTENT_TYPE);
        self::assertEquals(
            [
                'errors' => [
                    [
                        'status' => '404',
                        'title'  => 'not found http exception',
                        'detail' => 'Unsupported subresource.'
                    ]
                ]
            ],
            self::jsonToArray($response->getContent())
        );
    }

    public function testOptionsRelationshipWithUnacessibleTarget()
    {
        $entityType = $this->getEntityType(TestProduct::class);
        $response = $this->request(
            'OPTIONS',
            $this->getUrl(
                'oro_rest_api_subresource',
                self::processTemplateData([
                    'entity'      => $entityType,
                    'id'          => '@test_product->id',
                    'association' => 'unaccessible-target'
                ])
            )
        );

        self::assertResponseStatusCodeEquals($response, 404);
        self::assertResponseContentTypeEquals($response, self::JSON_API_CONTENT_TYPE);
        self::assertEquals(
            [
                'errors' => [
                    [
                        'status' => '404',
                        'title'  => 'not found http exception',
                        'detail' => 'Unsupported subresource.'
                    ]
                ]
            ],
            self::jsonToArray($response->getContent())
        );
    }

    public function testHeadRelationshipWithUnacessibleTarget()
    {
        $entityType = $this->getEntityType(TestProduct::class);
        $response = $this->request(
            'HEAD',
            $this->getUrl(
                'oro_rest_api_subresource',
                self::processTemplateData([
                    'entity'      => $entityType,
                    'id'          => '@test_product->id',
                    'association' => 'unaccessible-target'
                ])
            )
        );

        self::assertResponseStatusCodeEquals($response, 404);
        self::assertResponseContentTypeEquals($response, self::JSON_API_CONTENT_TYPE);
        self::assertEmpty($response->getContent());
    }
}
