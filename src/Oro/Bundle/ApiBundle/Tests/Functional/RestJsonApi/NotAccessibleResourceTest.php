<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApi;

use Oro\Bundle\ApiBundle\Model\EntityIdentifier;
use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity\TestDepartment;
use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity\TestEmployee;
use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;

class NotAccessibleResourceTest extends RestJsonApiTestCase
{
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
        if ('HEAD' === $method) {
            // the HEAD response should not have the content
            self::assertEmpty($response->getContent());
        }
        self::assertResponseContentTypeEquals($response, self::JSON_API_CONTENT_TYPE);
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
            ['entity' => $entityType, 'id' => '1'],
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
            ['data' => ['type' => $entityType, 'attributes' => ['name' => 'test']]],
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
            ['data' => ['type' => $entityType, 'attributes' => ['name' => 'test']]],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, 404);
        self::assertResponseContentTypeEquals($response, self::JSON_API_CONTENT_TYPE);
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
            ['entity' => $entityType, 'id' => '1'],
            ['data' => ['type' => $entityType, 'id' => '1', 'attributes' => ['name' => 'test']]],
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
            ['entity' => $entityType, 'id' => '1'],
            ['data' => ['type' => $entityType, 'id' => '1', 'attributes' => ['name' => 'test']]],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, 404);
        self::assertResponseContentTypeEquals($response, self::JSON_API_CONTENT_TYPE);
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
            ['entity' => $entityType, 'id' => '1'],
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
            ['entity' => $entityType, 'id' => '1'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, 404);
        self::assertResponseContentTypeEquals($response, self::JSON_API_CONTENT_TYPE);
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
    }

    public function testDisabledGetSubresource()
    {
        $this->appendEntityConfig(
            TestDepartment::class,
            ['actions' => ['get_subresource' => false]],
            true
        );

        $entityType = $this->getEntityType(TestDepartment::class);
        $response = $this->getSubresource(
            ['entity' => $entityType, 'id' => '1', 'association' => 'owner'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, 404);
        self::assertResponseContentTypeEquals($response, self::JSON_API_CONTENT_TYPE);
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
            ['entity' => $entityType, 'id' => '1', 'association' => 'owner'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, 404);
        self::assertResponseContentTypeEquals($response, self::JSON_API_CONTENT_TYPE);
    }

    public function testDisabledGetRelationship()
    {
        $this->appendEntityConfig(
            TestDepartment::class,
            ['actions' => ['get_relationship' => false]],
            true
        );

        $entityType = $this->getEntityType(TestDepartment::class);
        $response = $this->getRelationship(
            ['entity' => $entityType, 'id' => '1', 'association' => 'owner'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, 405);
        self::assertResponseContentTypeEquals($response, self::JSON_API_CONTENT_TYPE);
        self::assertEquals('PATCH', $response->headers->get('Allow'));
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
            ['entity' => $entityType, 'id' => '1', 'association' => 'owner'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, 404);
        self::assertResponseContentTypeEquals($response, self::JSON_API_CONTENT_TYPE);
    }

    public function testDisabledUpdateRelationship()
    {
        $this->appendEntityConfig(
            TestDepartment::class,
            ['actions' => ['update_relationship' => false]],
            true
        );

        $entityType = $this->getEntityType(TestDepartment::class);
        $response = $this->patchRelationship(
            ['entity' => $entityType, 'id' => '1', 'association' => 'owner'],
            ['data' => ['type' => $this->getEntityType(BusinessUnit::class), 'id' => '1']],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, 405);
        self::assertResponseContentTypeEquals($response, self::JSON_API_CONTENT_TYPE);
        self::assertEquals('GET', $response->headers->get('Allow'));
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
            ['entity' => $entityType, 'id' => '1', 'association' => 'owner'],
            ['data' => ['type' => $this->getEntityType(BusinessUnit::class), 'id' => '1']],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, 404);
        self::assertResponseContentTypeEquals($response, self::JSON_API_CONTENT_TYPE);
    }

    public function testDisabledAddRelationship()
    {
        $this->appendEntityConfig(
            TestDepartment::class,
            ['actions' => ['add_relationship' => false]],
            true
        );

        $entityType = $this->getEntityType(TestDepartment::class);
        $response = $this->postRelationship(
            ['entity' => $entityType, 'id' => '1', 'association' => 'staff'],
            ['data' => [['type' => $this->getEntityType(TestEmployee::class), 'id' => '1']]],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, 405);
        self::assertResponseContentTypeEquals($response, self::JSON_API_CONTENT_TYPE);
        self::assertEquals('GET, PATCH, DELETE', $response->headers->get('Allow'));
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
            ['entity' => $entityType, 'id' => '1', 'association' => 'staff'],
            ['data' => [['type' => $this->getEntityType(TestEmployee::class), 'id' => '1']]],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, 404);
        self::assertResponseContentTypeEquals($response, self::JSON_API_CONTENT_TYPE);
    }

    public function testDisabledDeleteRelationship()
    {
        $this->appendEntityConfig(
            TestDepartment::class,
            ['actions' => ['delete_relationship' => false]],
            true
        );

        $entityType = $this->getEntityType(TestDepartment::class);
        $response = $this->deleteRelationship(
            ['entity' => $entityType, 'id' => '1', 'association' => 'staff'],
            ['data' => [['type' => $this->getEntityType(TestEmployee::class), 'id' => '1']]],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, 405);
        self::assertResponseContentTypeEquals($response, self::JSON_API_CONTENT_TYPE);
        self::assertEquals('GET, PATCH, POST', $response->headers->get('Allow'));
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
            ['entity' => $entityType, 'id' => '1', 'association' => 'staff'],
            ['data' => [['type' => $this->getEntityType(TestEmployee::class), 'id' => '1']]],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, 404);
        self::assertResponseContentTypeEquals($response, self::JSON_API_CONTENT_TYPE);
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
            ['entity' => $entityType, 'id' => '1', 'association' => 'staff'],
            ['data' => [['type' => $this->getEntityType(TestEmployee::class), 'id' => '1']]],
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
            ['entity' => $entityType, 'id' => '1', 'association' => 'owner'],
            ['data' => [['type' => $this->getEntityType(BusinessUnit::class), 'id' => '1']]],
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
            ['entity' => $entityType, 'id' => '1', 'association' => 'owner'],
            ['data' => ['type' => $this->getEntityType(BusinessUnit::class), 'id' => '1']],
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
            ['entity' => $entityType, 'id' => '1', 'association' => 'owner'],
            ['data' => ['type' => $this->getEntityType(BusinessUnit::class), 'id' => '1']],
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
            $this->getUrl('oro_rest_api_item', ['entity' => $entityType, 'id' => '1'])
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
            $this->getUrl('oro_rest_api_item', ['entity' => $entityType, 'id' => '1'])
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
            $this->getUrl('oro_rest_api_item', ['entity' => $entityType, 'id' => '1'])
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
                ['entity' => $entityType, 'id' => '1', 'association' => 'staff']
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
                ['entity' => $entityType, 'id' => '1', 'association' => 'staff']
            )
        );
        self::assertResponseStatusCodeEquals($response, 405);
        self::assertResponseContentTypeEquals($response, self::JSON_API_CONTENT_TYPE);
        self::assertEquals('GET, PATCH, POST, DELETE', $response->headers->get('Allow'));
    }

    public function testPostMethodForSubresourceRoute()
    {
        $entityType = $this->getEntityType(TestDepartment::class);
        $response = $this->request(
            'POST',
            $this->getUrl(
                'oro_rest_api_subresource',
                ['entity' => $entityType, 'id' => '1', 'association' => 'staff']
            )
        );
        self::assertResponseStatusCodeEquals($response, 405);
        self::assertResponseContentTypeEquals($response, self::JSON_API_CONTENT_TYPE);
        self::assertEquals('GET', $response->headers->get('Allow'));
    }

    public function testPatchMethodForSubresourceRoute()
    {
        $entityType = $this->getEntityType(TestDepartment::class);
        $response = $this->request(
            'PATCH',
            $this->getUrl(
                'oro_rest_api_subresource',
                ['entity' => $entityType, 'id' => '1', 'association' => 'staff']
            )
        );
        self::assertResponseStatusCodeEquals($response, 405);
        self::assertResponseContentTypeEquals($response, self::JSON_API_CONTENT_TYPE);
        self::assertEquals('GET', $response->headers->get('Allow'));
    }

    public function testDeleteMethodForSubresourceRoute()
    {
        $entityType = $this->getEntityType(TestDepartment::class);
        $response = $this->request(
            'DELETE',
            $this->getUrl(
                'oro_rest_api_subresource',
                ['entity' => $entityType, 'id' => '1', 'association' => 'staff']
            )
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
                ['entity' => $entityType, 'id' => '1', 'association' => 'staff']
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
                ['entity' => $entityType, 'id' => '1', 'association' => 'staff']
            )
        );
        self::assertResponseStatusCodeEquals($response, 405);
        self::assertResponseContentTypeEquals($response, self::JSON_API_CONTENT_TYPE);
        self::assertEquals('GET', $response->headers->get('Allow'));
    }
}
