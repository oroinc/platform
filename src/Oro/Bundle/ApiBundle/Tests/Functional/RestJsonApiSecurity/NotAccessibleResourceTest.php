<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiSecurity;

use Oro\Bundle\ApiBundle\Model\EntityIdentifier;
use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity\TestBuyer;
use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity\TestDepartment;
use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity\TestEmployee;
use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity\TestOwner;
use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity\TestTarget;
use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\TestFrameworkBundle\Entity\TestProduct;
use Symfony\Component\HttpFoundation\Response;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class NotAccessibleResourceTest extends RestJsonApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([
            '@OroApiBundle/Tests/Functional/DataFixtures/not_accessible_resource.yml'
        ]);
    }

    /**
     * @dataProvider notAccessibleResourceActionsProvider
     */
    public function testNotAccessibleResource(string $method, string $route, array $routeParameters = [])
    {
        $entityType = $this->getEntityType(EntityIdentifier::class);
        $response = $this->request(
            $method,
            $this->getUrl($route, array_merge($routeParameters, ['entity' => $entityType]))
        );
        $this->assertResourceNotAccessibleResponse($response);
    }

    public function notAccessibleResourceActionsProvider(): array
    {
        return [
            ['GET', $this->getItemRouteName(), ['id' => 123]],
            ['GET', $this->getListRouteName()],
            ['PATCH', $this->getItemRouteName(), ['id' => 123]],
            ['POST', $this->getListRouteName()],
            ['DELETE', $this->getItemRouteName(), ['id' => 123]],
            ['DELETE', $this->getListRouteName()],
            ['GET', $this->getSubresourceRouteName(), ['id' => 123, 'association' => 'test']],
            ['GET', $this->getRelationshipRouteName(), ['id' => 123, 'association' => 'test']],
            ['PATCH', $this->getRelationshipRouteName(), ['id' => 123, 'association' => 'test']],
            ['POST', $this->getRelationshipRouteName(), ['id' => 123, 'association' => 'test']],
            ['DELETE', $this->getRelationshipRouteName(), ['id' => 123, 'association' => 'test']]
        ];
    }

    /**
     * @dataProvider unknownResourceActionsProvider
     */
    public function testUnknownResource(string $method, string $route, array $routeParameters = [])
    {
        $entityType = 'unknown_entity';
        $response = $this->request(
            $method,
            $this->getUrl($route, array_merge($routeParameters, ['entity' => $entityType]))
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_NOT_FOUND);
        self::assertResponseContentTypeEquals($response, self::JSON_API_CONTENT_TYPE);
        if ('HEAD' === $method) {
            // the HEAD response should not have the content
            self::assertEmpty($response->getContent());
        } else {
            $this->assertResponseContains(
                ['errors' => [['status' => (string)Response::HTTP_NOT_FOUND, 'title' => 'entity type constraint']]],
                $response
            );
        }
    }

    public function unknownResourceActionsProvider(): array
    {
        return [
            ['HEAD', $this->getItemRouteName(), ['id' => 123]],
            ['HEAD', $this->getListRouteName()],
            ['HEAD', $this->getSubresourceRouteName(), ['id' => 123, 'association' => 'test']],
            ['HEAD', $this->getRelationshipRouteName(), ['id' => 123, 'association' => 'test']],
            ['OPTIONS', $this->getItemRouteName(), ['id' => 123]],
            ['OPTIONS', $this->getListRouteName()],
            ['OPTIONS', $this->getSubresourceRouteName(), ['id' => 123, 'association' => 'test']],
            ['OPTIONS', $this->getRelationshipRouteName(), ['id' => 123, 'association' => 'test']],
            ['GET', $this->getItemRouteName(), ['id' => 123]],
            ['GET', $this->getListRouteName()],
            ['GET', $this->getSubresourceRouteName(), ['id' => 123, 'association' => 'test']],
            ['GET', $this->getRelationshipRouteName(), ['id' => 123, 'association' => 'test']],
            ['POST', $this->getItemRouteName(), ['id' => 123]],
            ['POST', $this->getListRouteName()],
            ['POST', $this->getSubresourceRouteName(), ['id' => 123, 'association' => 'test']],
            ['POST', $this->getRelationshipRouteName(), ['id' => 123, 'association' => 'test']],
            ['PATCH', $this->getItemRouteName(), ['id' => 123]],
            ['PATCH', $this->getListRouteName()],
            ['PATCH', $this->getSubresourceRouteName(), ['id' => 123, 'association' => 'test']],
            ['PATCH', $this->getRelationshipRouteName(), ['id' => 123, 'association' => 'test']],
            ['DELETE', $this->getItemRouteName(), ['id' => 123]],
            ['DELETE', $this->getListRouteName()],
            ['DELETE', $this->getSubresourceRouteName(), ['id' => 123, 'association' => 'test']],
            ['DELETE', $this->getRelationshipRouteName(), ['id' => 123, 'association' => 'test']]
        ];
    }

    public function testGetWithRelationshipThatContainsNotAccessibleTarget()
    {
        $entityType = $this->getEntityType(TestDepartment::class);
        $accessibleEntityType = $this->getEntityType(TestEmployee::class);
        $notAccessibleEntityType = $this->getEntityType(TestBuyer::class);
        $response = $this->get(
            ['entity' => $entityType, 'id' => '<toString(@test_department->id)>'],
            ['fields' => [$entityType => 'staff']]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type'          => $entityType,
                    'id'            => '<toString(@test_department->id)>',
                    'relationships' => [
                        'staff' => [
                            'data' => [
                                ['type' => $accessibleEntityType, 'id' => '<toString(@test_employee1->id)>'],
                                ['type' => $notAccessibleEntityType, 'id' => '<toString(@test_buyer1->id)>']
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );
        $responseContent = self::jsonToArray($response->getContent());
        self::assertArrayNotHasKey('included', $responseContent);
    }

    public function testGetWithRelationshipThatContainsNotAccessibleTargetAndWithIncludeFilter()
    {
        $entityType = $this->getEntityType(TestDepartment::class);
        $accessibleEntityType = $this->getEntityType(TestEmployee::class);
        $notAccessibleEntityType = $this->getEntityType(TestBuyer::class);
        $response = $this->get(
            ['entity' => $entityType, 'id' => '<toString(@test_department->id)>'],
            [
                'fields'  => [
                    $entityType              => 'staff',
                    $accessibleEntityType    => 'name',
                    $notAccessibleEntityType => 'name'
                ],
                'include' => 'staff'
            ]
        );
        $this->assertResponseContains(
            [
                'data'     => [
                    'type'          => $entityType,
                    'id'            => '<toString(@test_department->id)>',
                    'relationships' => [
                        'staff' => [
                            'data' => [
                                ['type' => $notAccessibleEntityType, 'id' => '<toString(@test_buyer1->id)>'],
                                ['type' => $accessibleEntityType, 'id' => '<toString(@test_employee1->id)>']
                            ]
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type'       => $accessibleEntityType,
                        'id'         => '<toString(@test_employee1->id)>',
                        'attributes' => [
                            'name' => 'Test Employee 1'
                        ]
                    ]
                ]
            ],
            $response
        );
        $responseContent = self::jsonToArray($response->getContent());
        self::assertCount(1, $responseContent['included'], 'included');
        self::assertArrayNotHasKey('meta', $responseContent['included'][0], 'included[0]');
    }

    public function testGetRelationshipThatContainsNotAccessibleTarget()
    {
        $entityType = $this->getEntityType(TestDepartment::class);
        $accessibleEntityType = $this->getEntityType(TestEmployee::class);
        $notAccessibleEntityType = $this->getEntityType(TestBuyer::class);
        $response = $this->getRelationship(
            ['entity' => $entityType, 'id' => '<toString(@test_department->id)>', 'association' => 'staff']
        );
        $this->assertResponseContains(
            [
                'data' => [
                    ['type' => $notAccessibleEntityType, 'id' => '<toString(@test_buyer1->id)>'],
                    ['type' => $accessibleEntityType, 'id' => '<toString(@test_employee1->id)>']
                ]
            ],
            $response,
            true
        );
        $responseContent = self::jsonToArray($response->getContent());
        foreach ($responseContent['data'] as $key => $item) {
            self::assertArrayNotHasKey('meta', $item, sprintf('data[%s]', $key));
            self::assertArrayNotHasKey('attributes', $item, sprintf('data[%s]', $key));
            self::assertArrayNotHasKey('relationships', $item, sprintf('data[%s]', $key));
        }
    }

    public function testGetSubresourceThatContainsNotAccessibleTarget()
    {
        $entityType = $this->getEntityType(TestDepartment::class);
        $accessibleEntityType = $this->getEntityType(TestEmployee::class);
        $notAccessibleEntityType = $this->getEntityType(TestBuyer::class);
        $response = $this->getSubresource(
            ['entity' => $entityType, 'id' => '<toString(@test_department->id)>', 'association' => 'staff']
        );
        $this->assertResponseContains(
            [
                'data' => [
                    ['type' => $notAccessibleEntityType, 'id' => '<toString(@test_buyer1->id)>'],
                    [
                        'type'          => $accessibleEntityType,
                        'id'            => '<toString(@test_employee1->id)>',
                        'attributes'    => [
                            'name' => 'Test Employee 1'
                        ],
                        'relationships' => [
                            'department'   => [
                                'data' => ['type' => $entityType, 'id' => '<toString(@test_department->id)>']
                            ],
                            'organization' => [
                                'data' => ['type' => 'organizations', 'id' => '<toString(@organization->id)>']
                            ]
                        ]
                    ]
                ]
            ],
            $response,
            true
        );
        $responseContent = self::jsonToArray($response->getContent());
        foreach ($responseContent['data'] as $key => $item) {
            self::assertArrayNotHasKey('meta', $item, sprintf('data[%s]', $key));
            if ($item['type'] === $notAccessibleEntityType) {
                self::assertArrayNotHasKey('attributes', $item, sprintf('data[%s]', $key));
                self::assertArrayNotHasKey('relationships', $item, sprintf('data[%s]', $key));
            }
        }
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
            ['entity' => $entityType, 'id' => '<toString(@test_department->id)>'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_NOT_FOUND);
        self::assertResponseContentTypeEquals($response, self::JSON_API_CONTENT_TYPE);
        self::assertEmpty($response->getContent());
    }

    public function testDisabledGetWhenGetListIsDisabled()
    {
        $this->appendEntityConfig(
            TestDepartment::class,
            ['actions' => ['get' => false, 'get_list' => false]],
            true
        );

        $entityType = $this->getEntityType(TestDepartment::class);
        $response = $this->get(
            ['entity' => $entityType, 'id' => '<toString(@test_department->id)>'],
            [],
            [],
            false
        );
        $this->assertResourceNotAccessibleResponse($response);
    }

    public function testGetListWhenGetIsDisabled()
    {
        $this->appendEntityConfig(
            TestDepartment::class,
            ['actions' => ['get' => false]],
            true
        );

        $entityType = $this->getEntityType(TestDepartment::class);
        $response = $this->cget(['entity' => $entityType]);
        $this->assertResponseContains(
            [
                'data' => [
                    ['type' => $entityType, 'id' => '<toString(@test_department->id)>']
                ]
            ],
            $response
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
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, PATCH, POST, DELETE');
        self::assertResponseContentTypeEquals($response, self::JSON_API_CONTENT_TYPE);
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
        $this->assertResourceNotAccessibleResponse($response);
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
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET, PATCH, DELETE');
        self::assertResponseContentTypeEquals($response, self::JSON_API_CONTENT_TYPE);
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
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET, DELETE');
        self::assertResponseContentTypeEquals($response, self::JSON_API_CONTENT_TYPE);
    }

    public function testDisabledCreateWhenGetAndGetListAreDisabled()
    {
        $this->appendEntityConfig(
            TestDepartment::class,
            ['actions' => ['create' => false, 'get' => false, 'get_list' => false]],
            true
        );

        $entityType = $this->getEntityType(TestDepartment::class);
        $response = $this->post(
            ['entity' => $entityType],
            [],
            [],
            false
        );
        $this->assertResourceNotAccessibleResponse($response);
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
            ['entity' => $entityType, 'id' => '<toString(@test_department->id)>'],
            [],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET, DELETE');
        self::assertResponseContentTypeEquals($response, self::JSON_API_CONTENT_TYPE);
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
            ['entity' => $entityType, 'id' => '<toString(@test_department->id)>'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_NOT_FOUND);
        self::assertResponseContentTypeEquals($response, self::JSON_API_CONTENT_TYPE);
        self::assertEmpty($response->getContent());
    }

    public function testDisabledUpdateWhenGetAndGetListAreDisabled()
    {
        $this->appendEntityConfig(
            TestDepartment::class,
            ['actions' => ['update' => false, 'get' => false, 'get_list' => false]],
            true
        );

        $entityType = $this->getEntityType(TestDepartment::class);
        $response = $this->patch(
            ['entity' => $entityType, 'id' => '<toString(@test_department->id)>'],
            [],
            [],
            false
        );
        $this->assertResourceNotAccessibleResponse($response);
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
            ['entity' => $entityType, 'id' => '<toString(@test_department->id)>'],
            [],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET, PATCH');
        self::assertResponseContentTypeEquals($response, self::JSON_API_CONTENT_TYPE);
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
            ['entity' => $entityType, 'id' => '<toString(@test_department->id)>'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_NOT_FOUND);
        self::assertResponseContentTypeEquals($response, self::JSON_API_CONTENT_TYPE);
        self::assertEmpty($response->getContent());
    }

    public function testDisabledDeleteWhenGetAndGetListAreDisabled()
    {
        $this->appendEntityConfig(
            TestDepartment::class,
            ['actions' => ['delete' => false, 'get' => false, 'get_list' => false]],
            true
        );

        $entityType = $this->getEntityType(TestDepartment::class);
        $response = $this->delete(
            ['entity' => $entityType, 'id' => '<toString(@test_department->id)>'],
            [],
            [],
            false
        );
        $this->assertResourceNotAccessibleResponse($response);
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
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET, PATCH, POST');
        self::assertResponseContentTypeEquals($response, self::JSON_API_CONTENT_TYPE);
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
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
        self::assertResponseContentTypeEquals($response, self::JSON_API_CONTENT_TYPE);
    }

    public function testDisabledDeleteListWhenGetAndGetListAreDisabled()
    {
        $this->appendEntityConfig(
            TestDepartment::class,
            ['actions' => ['delete_list' => false, 'get' => false, 'get_list' => false]],
            true
        );

        $entityType = $this->getEntityType(TestDepartment::class);
        $response = $this->cdelete(
            ['entity' => $entityType],
            [],
            [],
            false
        );
        $this->assertResourceNotAccessibleResponse($response);
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
            ['entity' => $entityType, 'id' => '<toString(@test_department->id)>', 'association' => 'owner'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_NOT_FOUND);
        self::assertResponseContentTypeEquals($response, self::JSON_API_CONTENT_TYPE);
        self::assertEmpty($response->getContent());
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
            ['entity' => $entityType, 'id' => '<toString(@test_department->id)>', 'association' => 'owner'],
            [],
            [],
            false
        );
        $this->assertUnsupportedSubresourceResponse($response);
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
            ['entity' => $entityType, 'id' => '<toString(@test_department->id)>', 'association' => 'owner'],
            [],
            [],
            false
        );
        $this->assertResourceNotAccessibleResponse($response);
    }

    public function testDisabledGetSubresourceWhenGetAndGetListAreDisabled()
    {
        $this->appendEntityConfig(
            TestDepartment::class,
            ['actions' => ['get_subresource' => false, 'get' => false, 'get_list' => false]],
            true
        );

        $entityType = $this->getEntityType(TestDepartment::class);
        $response = $this->getSubresource(
            ['entity' => $entityType, 'id' => '<toString(@test_department->id)>', 'association' => 'owner'],
            [],
            [],
            false
        );
        $this->assertResourceNotAccessibleResponse($response);
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
            ['entity' => $entityType, 'id' => '<toString(@test_department->id)>', 'association' => 'owner'],
            [],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, PATCH');
        self::assertResponseContentTypeEquals($response, self::JSON_API_CONTENT_TYPE);
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
            ['entity' => $entityType, 'id' => '<toString(@test_department->id)>', 'association' => 'owner'],
            [],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, PATCH');
        self::assertResponseContentTypeEquals($response, self::JSON_API_CONTENT_TYPE);
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
            ['entity' => $entityType, 'id' => '<toString(@test_department->id)>', 'association' => 'owner'],
            [],
            [],
            false
        );
        $this->assertUnsupportedSubresourceResponse($response);
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
            ['entity' => $entityType, 'id' => '<toString(@test_department->id)>', 'association' => 'owner'],
            [],
            [],
            false
        );
        $this->assertResourceNotAccessibleResponse($response);
    }

    public function testDisabledGetRelationshipWhenGetAndGetListAreDisabled()
    {
        $this->appendEntityConfig(
            TestDepartment::class,
            ['actions' => ['get_relationship' => false, 'get' => false, 'get_list' => false]],
            true
        );

        $entityType = $this->getEntityType(TestDepartment::class);
        $response = $this->getRelationship(
            ['entity' => $entityType, 'id' => '<toString(@test_department->id)>', 'association' => 'owner'],
            [],
            [],
            false
        );
        $this->assertResourceNotAccessibleResponse($response);
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
            ['entity' => $entityType, 'id' => '<toString(@test_department->id)>', 'association' => 'owner'],
            [],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
        self::assertResponseContentTypeEquals($response, self::JSON_API_CONTENT_TYPE);
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
            ['entity' => $entityType, 'id' => '<toString(@test_department->id)>', 'association' => 'owner'],
            [],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
        self::assertResponseContentTypeEquals($response, self::JSON_API_CONTENT_TYPE);
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
            ['entity' => $entityType, 'id' => '<toString(@test_department->id)>', 'association' => 'owner'],
            [],
            [],
            false
        );
        $this->assertUnsupportedSubresourceResponse($response);
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
            ['entity' => $entityType, 'id' => '<toString(@test_department->id)>', 'association' => 'owner'],
            [],
            [],
            false
        );
        $this->assertResourceNotAccessibleResponse($response);
    }

    public function testDisabledUpdateRelationshipWhenGetAndGetListAreDisabled()
    {
        $this->appendEntityConfig(
            TestDepartment::class,
            ['actions' => ['update_relationship' => false, 'get' => false, 'get_list' => false]],
            true
        );

        $entityType = $this->getEntityType(TestDepartment::class);
        $response = $this->patchRelationship(
            ['entity' => $entityType, 'id' => '<toString(@test_department->id)>', 'association' => 'owner'],
            [],
            [],
            false
        );
        $this->assertResourceNotAccessibleResponse($response);
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
            ['entity' => $entityType, 'id' => '<toString(@test_department->id)>', 'association' => 'staff'],
            [],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET, PATCH, DELETE');
        self::assertResponseContentTypeEquals($response, self::JSON_API_CONTENT_TYPE);
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
            ['entity' => $entityType, 'id' => '<toString(@test_department->id)>', 'association' => 'staff'],
            [],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET, PATCH, DELETE');
        self::assertResponseContentTypeEquals($response, self::JSON_API_CONTENT_TYPE);
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
            ['entity' => $entityType, 'id' => '<toString(@test_department->id)>', 'association' => 'staff'],
            [],
            [],
            false
        );
        $this->assertUnsupportedSubresourceResponse($response);
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
            ['entity' => $entityType, 'id' => '<toString(@test_department->id)>', 'association' => 'staff'],
            [],
            [],
            false
        );
        $this->assertResourceNotAccessibleResponse($response);
    }

    public function testDisabledAddRelationshipWhenGetAndGetListAreDisabled()
    {
        $this->appendEntityConfig(
            TestDepartment::class,
            ['actions' => ['add_relationship' => false, 'get' => false, 'get_list' => false]],
            true
        );

        $entityType = $this->getEntityType(TestDepartment::class);
        $response = $this->postRelationship(
            ['entity' => $entityType, 'id' => '<toString(@test_department->id)>', 'association' => 'staff'],
            [],
            [],
            false
        );
        $this->assertResourceNotAccessibleResponse($response);
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
            ['entity' => $entityType, 'id' => '<toString(@test_department->id)>', 'association' => 'staff'],
            [],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET, PATCH, POST');
        self::assertResponseContentTypeEquals($response, self::JSON_API_CONTENT_TYPE);
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
            ['entity' => $entityType, 'id' => '<toString(@test_department->id)>', 'association' => 'staff'],
            [],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET, PATCH, POST');
        self::assertResponseContentTypeEquals($response, self::JSON_API_CONTENT_TYPE);
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
            ['entity' => $entityType, 'id' => '<toString(@test_department->id)>', 'association' => 'staff'],
            [],
            [],
            false
        );
        $this->assertUnsupportedSubresourceResponse($response);
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
            ['entity' => $entityType, 'id' => '<toString(@test_department->id)>', 'association' => 'staff'],
            [],
            [],
            false
        );
        $this->assertResourceNotAccessibleResponse($response);
    }

    public function testDisabledDeleteRelationshipWhenGetAndGetListAreDisabled()
    {
        $this->appendEntityConfig(
            TestDepartment::class,
            ['actions' => ['delete_relationship' => false, 'get' => false, 'get_list' => false]],
            true
        );

        $entityType = $this->getEntityType(TestDepartment::class);
        $response = $this->deleteRelationship(
            ['entity' => $entityType, 'id' => '<toString(@test_department->id)>', 'association' => 'staff'],
            [],
            [],
            false
        );
        $this->assertResourceNotAccessibleResponse($response);
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
            ['entity' => $entityType, 'id' => '<toString(@test_department->id)>', 'association' => 'staff'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_NOT_FOUND);
        self::assertResponseContentTypeEquals($response, self::JSON_API_CONTENT_TYPE);
        self::assertEmpty($response->getContent());
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
            ['entity' => $entityType, 'id' => '<toString(@test_department->id)>', 'association' => 'owner'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_NOT_FOUND);
        self::assertResponseContentTypeEquals($response, self::JSON_API_CONTENT_TYPE);
        self::assertEmpty($response->getContent());
    }

    public function testAddRelationshipForToOneAssociation()
    {
        $entityType = $this->getEntityType(TestDepartment::class);
        $response = $this->postRelationship(
            ['entity' => $entityType, 'id' => '<toString(@test_department->id)>', 'association' => 'owner'],
            [],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET, PATCH');
        self::assertResponseContentTypeEquals($response, self::JSON_API_CONTENT_TYPE);
    }

    public function testDeleteRelationshipToOneAssociation()
    {
        $entityType = $this->getEntityType(TestDepartment::class);
        $response = $this->deleteRelationship(
            ['entity' => $entityType, 'id' => '<toString(@test_department->id)>', 'association' => 'owner'],
            [],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET, PATCH');
        self::assertResponseContentTypeEquals($response, self::JSON_API_CONTENT_TYPE);
    }

    public function testPostMethodForSingleItemPrimaryResourceRoute()
    {
        $entityType = $this->getEntityType(TestDepartment::class);
        $response = $this->request(
            'POST',
            $this->getUrl(
                $this->getItemRouteName(),
                self::processTemplateData([
                    'entity' => $entityType,
                    'id'     => '<toString(@test_department->id)>'
                ])
            )
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET, PATCH, DELETE');
        self::assertResponseContentTypeEquals($response, self::JSON_API_CONTENT_TYPE);
    }

    public function testOptionsMethodForSingleItemPrimaryResourceRoute()
    {
        $entityType = $this->getEntityType(TestDepartment::class);
        $response = $this->options(
            $this->getItemRouteName(),
            self::processTemplateData([
                'entity' => $entityType,
                'id'     => '<toString(@test_department->id)>'
            ])
        );
        self::assertAllowResponseHeader($response, 'OPTIONS, GET, PATCH, DELETE');
    }

    public function testHeadMethodForSingleItemPrimaryResourceRoute()
    {
        $entityType = $this->getEntityType(TestDepartment::class);
        $response = $this->request(
            'HEAD',
            $this->getUrl(
                $this->getItemRouteName(),
                self::processTemplateData([
                    'entity' => $entityType,
                    'id'     => '<toString(@test_department->id)>'
                ])
            )
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET, PATCH, DELETE');
        self::assertResponseContentTypeEquals($response, self::JSON_API_CONTENT_TYPE);
    }

    public function testOptionsMethodForListPrimaryResourceRoute()
    {
        $entityType = $this->getEntityType(TestDepartment::class);
        $response = $this->options($this->getListRouteName(), ['entity' => $entityType]);
        self::assertAllowResponseHeader($response, 'OPTIONS, GET, PATCH, POST, DELETE');
    }

    public function testHeadMethodForListPrimaryResourceRoute()
    {
        $entityType = $this->getEntityType(TestDepartment::class);
        $response = $this->request(
            'HEAD',
            $this->getUrl($this->getListRouteName(), ['entity' => $entityType])
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET, PATCH, POST, DELETE');
        self::assertResponseContentTypeEquals($response, self::JSON_API_CONTENT_TYPE);
    }

    public function testOptionsMethodForRelationshipRoute()
    {
        $entityType = $this->getEntityType(TestDepartment::class);
        $response = $this->options(
            $this->getRelationshipRouteName(),
            self::processTemplateData([
                'entity'      => $entityType,
                'id'          => '<toString(@test_department->id)>',
                'association' => 'staff'
            ])
        );
        self::assertAllowResponseHeader($response, 'OPTIONS, GET, PATCH, POST, DELETE');
    }

    public function testHeadMethodForRelationshipRoute()
    {
        $entityType = $this->getEntityType(TestDepartment::class);
        $response = $this->request(
            'HEAD',
            $this->getUrl(
                $this->getRelationshipRouteName(),
                self::processTemplateData([
                    'entity'      => $entityType,
                    'id'          => '<toString(@test_department->id)>',
                    'association' => 'staff'
                ])
            )
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET, PATCH, POST, DELETE');
        self::assertResponseContentTypeEquals($response, self::JSON_API_CONTENT_TYPE);
    }

    public function testPostMethodForSubresourceRoute()
    {
        $entityType = $this->getEntityType(TestDepartment::class);
        $response = $this->postSubresource(
            ['entity' => $entityType, 'id' => '<toString(@test_department->id)>', 'association' => 'staff'],
            [],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
        self::assertResponseContentTypeEquals($response, self::JSON_API_CONTENT_TYPE);
    }

    public function testPatchMethodForSubresourceRoute()
    {
        $entityType = $this->getEntityType(TestDepartment::class);
        $response = $this->patchSubresource(
            ['entity' => $entityType, 'id' => '<toString(@test_department->id)>', 'association' => 'staff'],
            [],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
        self::assertResponseContentTypeEquals($response, self::JSON_API_CONTENT_TYPE);
    }

    public function testDeleteMethodForSubresourceRoute()
    {
        $entityType = $this->getEntityType(TestDepartment::class);
        $response = $this->deleteSubresource(
            ['entity' => $entityType, 'id' => '<toString(@test_department->id)>', 'association' => 'staff'],
            [],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
        self::assertResponseContentTypeEquals($response, self::JSON_API_CONTENT_TYPE);
    }

    public function testOptionsMethodForSubresourceRoute()
    {
        $entityType = $this->getEntityType(TestDepartment::class);
        $response = $this->options(
            $this->getSubresourceRouteName(),
            self::processTemplateData([
                'entity'      => $entityType,
                'id'          => '<toString(@test_department->id)>',
                'association' => 'staff'
            ])
        );
        self::assertAllowResponseHeader($response, 'OPTIONS, GET');
    }

    public function testHeadMethodForSubresourceRoute()
    {
        $entityType = $this->getEntityType(TestDepartment::class);
        $response = $this->request(
            'HEAD',
            $this->getUrl(
                $this->getSubresourceRouteName(),
                self::processTemplateData([
                    'entity'      => $entityType,
                    'id'          => '<toString(@test_department->id)>',
                    'association' => 'staff'
                ])
            )
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
        self::assertResponseContentTypeEquals($response, self::JSON_API_CONTENT_TYPE);
    }

    public function testGetSubresourceWithUnaccessibleTarget()
    {
        $entityType = $this->getEntityType(TestProduct::class);
        $response = $this->getSubresource(
            ['entity' => $entityType, 'id' => '@test_product->id', 'association' => 'unaccessible-target'],
            [],
            [],
            false
        );
        $this->assertUnsupportedSubresourceResponse($response);
    }

    public function testPostSubresourceWithUnaccessibleTarget()
    {
        $entityType = $this->getEntityType(TestProduct::class);
        $response = $this->postSubresource(
            ['entity' => $entityType, 'id' => '@test_product->id', 'association' => 'unaccessible-target'],
            [],
            [],
            false
        );
        $this->assertUnsupportedSubresourceResponse($response);
    }

    public function testPatchSubresourceWithUnaccessibleTarget()
    {
        $entityType = $this->getEntityType(TestProduct::class);
        $response = $this->patchSubresource(
            ['entity' => $entityType, 'id' => '@test_product->id', 'association' => 'unaccessible-target'],
            [],
            [],
            false
        );
        $this->assertUnsupportedSubresourceResponse($response);
    }

    public function testDeleteSubresourceWithUnaccessibleTarget()
    {
        $entityType = $this->getEntityType(TestProduct::class);
        $response = $this->deleteSubresource(
            ['entity' => $entityType, 'id' => '@test_product->id', 'association' => 'unaccessible-target'],
            [],
            [],
            false
        );
        $this->assertUnsupportedSubresourceResponse($response);
    }

    public function testGetRelationshipWithUnaccessibleTarget()
    {
        $entityType = $this->getEntityType(TestProduct::class);
        $response = $this->getRelationship(
            ['entity' => $entityType, 'id' => '@test_product->id', 'association' => 'unaccessible-target'],
            [],
            [],
            false
        );
        $this->assertUnsupportedSubresourceResponse($response);
    }

    public function testPostRelationshipWithUnaccessibleTarget()
    {
        $entityType = $this->getEntityType(TestProduct::class);
        $response = $this->postRelationship(
            ['entity' => $entityType, 'id' => '@test_product->id', 'association' => 'unaccessible-target'],
            [],
            [],
            false
        );
        $this->assertUnsupportedSubresourceResponse($response);
    }

    public function testPatchRelationshipWithUnaccessibleTarget()
    {
        $entityType = $this->getEntityType(TestProduct::class);
        $response = $this->patchRelationship(
            ['entity' => $entityType, 'id' => '@test_product->id', 'association' => 'unaccessible-target'],
            [],
            [],
            false
        );
        $this->assertUnsupportedSubresourceResponse($response);
    }

    public function testDeleteRelationshipWithUnaccessibleTarget()
    {
        $entityType = $this->getEntityType(TestProduct::class);
        $response = $this->deleteRelationship(
            ['entity' => $entityType, 'id' => '@test_product->id', 'association' => 'unaccessible-target'],
            [],
            [],
            false
        );
        $this->assertUnsupportedSubresourceResponse($response);
    }

    public function testOptionsRelationshipWithUnaccessibleTarget()
    {
        $entityType = $this->getEntityType(TestProduct::class);
        $response = $this->options(
            $this->getSubresourceRouteName(),
            ['entity' => $entityType, 'id' => '@test_product->id', 'association' => 'unaccessible-target'],
            [],
            false
        );
        $this->assertUnsupportedSubresourceResponse($response);
    }

    public function testHeadRelationshipWithUnaccessibleTarget()
    {
        $entityType = $this->getEntityType(TestProduct::class);
        $response = $this->request(
            'HEAD',
            $this->getUrl(
                $this->getSubresourceRouteName(),
                self::processTemplateData([
                    'entity'      => $entityType,
                    'id'          => '@test_product->id',
                    'association' => 'unaccessible-target'
                ])
            )
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_NOT_FOUND);
        self::assertResponseContentTypeEquals($response, self::JSON_API_CONTENT_TYPE);
        self::assertEmpty($response->getContent());
    }

    public function testGetForExtendedAssociationWithUnaccessibleTarget()
    {
        $this->appendEntityConfig(TestTarget::class, ['actions' => false], true);
        $response = $this->get(
            ['entity' => 'testapiactivities', 'id' => '<toString(@test_activity->id)>'],
            ['include' => 'activityTargets']
        );
        $this->assertResponseContains(
            [
                'data'     => [
                    'type'          => 'testapiactivities',
                    'id'            => '<toString(@test_activity->id)>',
                    'relationships' => [
                        'activityTargets' => [
                            'data' => [
                                ['type' => 'testapiowners', 'id' => '<toString(@test_owner1->id)>']
                            ]
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type'          => 'testapiowners',
                        'id'            => '<toString(@test_owner1->id)>',
                        'relationships' => [
                            'activityTestActivities' => [
                                'data' => [
                                    ['type' => 'testapiactivities', 'id' => '<toString(@test_activity->id)>']
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );
        $responseData = self::jsonToArray($response->getContent());
        self::assertCount(1, $responseData['included']);
        self::assertArrayNotHasKey('target', $responseData['included'][0]['relationships']);
        self::assertArrayNotHasKey('targets', $responseData['included'][0]['relationships']);
    }

    public function testGetForExtendedAssociationWhenAllTargetsAreUnaccessible()
    {
        $this->appendEntityConfig(TestTarget::class, ['actions' => false], true);
        $this->appendEntityConfig(TestOwner::class, ['actions' => false], true);
        $response = $this->get(
            ['entity' => 'testapiactivities', 'id' => '<toString(@test_activity->id)>'],
            ['include' => 'activityTargets']
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type'          => 'testapiactivities',
                    'id'            => '<toString(@test_activity->id)>',
                    'relationships' => [
                        'activityTargets' => [
                            'data' => []
                        ]
                    ]
                ]
            ],
            $response
        );
        $responseData = self::jsonToArray($response->getContent());
        self::assertArrayNotHasKey('included', $responseData);
    }

    public function testGetRelationshipForExtendedAssociationWhenAllTargetsAreUnaccessible()
    {
        $this->appendEntityConfig(TestTarget::class, ['actions' => false], true);
        $this->appendEntityConfig(TestOwner::class, ['actions' => false], true);
        $response = $this->getRelationship(
            [
                'entity'      => 'testapiactivities',
                'id'          => '<toString(@test_activity->id)>',
                'association' => 'activityTargets'
            ]
        );
        $this->assertResponseContains(['data' => []], $response);
    }

    public function testGetSubresourceForExtendedAssociationWhenAllTargetsAreUnaccessible()
    {
        $this->appendEntityConfig(TestTarget::class, ['actions' => false], true);
        $this->appendEntityConfig(TestOwner::class, ['actions' => false], true);
        $response = $this->getSubresource(
            [
                'entity'      => 'testapiactivities',
                'id'          => '<toString(@test_activity->id)>',
                'association' => 'activityTargets'
            ]
        );
        $this->assertResponseContains(['data' => []], $response);
    }
}
