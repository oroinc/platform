<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApi;

use Oro\Bundle\ApiBundle\Model\EntityIdentifier;
use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity\TestDepartment;
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

        $this->assertResponseValidationError(
            [
                'title'  => 'resource not accessible exception',
                'detail' => 'The resource is not accessible.'
            ],
            $response,
            Response::HTTP_NOT_FOUND
        );
    }

    /**
     * @return array
     */
    public function notAccessibleResourceActionsProvider()
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

    /**
     * @return array
     */
    public function unknownResourceActionsProvider()
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

        $this->assertResponseValidationError(
            [
                'title'  => 'resource not accessible exception',
                'detail' => 'The resource is not accessible.'
            ],
            $response,
            Response::HTTP_NOT_FOUND
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
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, POST, DELETE');
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

        $this->assertResponseValidationError(
            [
                'title'  => 'resource not accessible exception',
                'detail' => 'The resource is not accessible.'
            ],
            $response,
            Response::HTTP_NOT_FOUND
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
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET, DELETE');
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

        $this->assertResponseValidationError(
            [
                'title'  => 'resource not accessible exception',
                'detail' => 'The resource is not accessible.'
            ],
            $response,
            Response::HTTP_NOT_FOUND
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
            ['entity' => $entityType, 'id' => '@test_department->id'],
            [],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title'  => 'resource not accessible exception',
                'detail' => 'The resource is not accessible.'
            ],
            $response,
            Response::HTTP_NOT_FOUND
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
            ['entity' => $entityType, 'id' => '@test_department->id'],
            [],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title'  => 'resource not accessible exception',
                'detail' => 'The resource is not accessible.'
            ],
            $response,
            Response::HTTP_NOT_FOUND
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
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET, POST');
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

        $this->assertResponseValidationError(
            [
                'title'  => 'resource not accessible exception',
                'detail' => 'The resource is not accessible.'
            ],
            $response,
            Response::HTTP_NOT_FOUND
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

        self::assertResponseStatusCodeEquals($response, Response::HTTP_NOT_FOUND);
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

        $this->assertResponseValidationError(
            [
                'title'  => 'relationship constraint',
                'detail' => 'Unsupported subresource.'
            ],
            $response,
            Response::HTTP_NOT_FOUND
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

        $this->assertResponseValidationError(
            [
                'title'  => 'resource not accessible exception',
                'detail' => 'The resource is not accessible.'
            ],
            $response,
            Response::HTTP_NOT_FOUND
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
            ['entity' => $entityType, 'id' => '@test_department->id', 'association' => 'owner'],
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
            ['entity' => $entityType, 'id' => '@test_department->id', 'association' => 'owner'],
            [],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title'  => 'relationship constraint',
                'detail' => 'Unsupported subresource.'
            ],
            $response,
            Response::HTTP_NOT_FOUND
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

        $this->assertResponseValidationError(
            [
                'title'  => 'resource not accessible exception',
                'detail' => 'The resource is not accessible.'
            ],
            $response,
            Response::HTTP_NOT_FOUND
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
            ['entity' => $entityType, 'id' => '@test_department->id', 'association' => 'owner'],
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
            ['entity' => $entityType, 'id' => '@test_department->id', 'association' => 'owner'],
            [],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title'  => 'relationship constraint',
                'detail' => 'Unsupported subresource.'
            ],
            $response,
            Response::HTTP_NOT_FOUND
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

        $this->assertResponseValidationError(
            [
                'title'  => 'resource not accessible exception',
                'detail' => 'The resource is not accessible.'
            ],
            $response,
            Response::HTTP_NOT_FOUND
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
            ['entity' => $entityType, 'id' => '@test_department->id', 'association' => 'staff'],
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
            ['entity' => $entityType, 'id' => '@test_department->id', 'association' => 'staff'],
            [],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title'  => 'relationship constraint',
                'detail' => 'Unsupported subresource.'
            ],
            $response,
            Response::HTTP_NOT_FOUND
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

        $this->assertResponseValidationError(
            [
                'title'  => 'resource not accessible exception',
                'detail' => 'The resource is not accessible.'
            ],
            $response,
            Response::HTTP_NOT_FOUND
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
            ['entity' => $entityType, 'id' => '@test_department->id', 'association' => 'staff'],
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
            ['entity' => $entityType, 'id' => '@test_department->id', 'association' => 'staff'],
            [],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title'  => 'relationship constraint',
                'detail' => 'Unsupported subresource.'
            ],
            $response,
            Response::HTTP_NOT_FOUND
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

        $this->assertResponseValidationError(
            [
                'title'  => 'resource not accessible exception',
                'detail' => 'The resource is not accessible.'
            ],
            $response,
            Response::HTTP_NOT_FOUND
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
        self::assertResponseStatusCodeEquals($response, Response::HTTP_NOT_FOUND);
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
        self::assertResponseStatusCodeEquals($response, Response::HTTP_NOT_FOUND);
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
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET, PATCH');
        self::assertResponseContentTypeEquals($response, self::JSON_API_CONTENT_TYPE);
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
                self::processTemplateData(['entity' => $entityType, 'id' => '@test_department->id'])
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
            ['entity' => $entityType, 'id' => '@test_department->id']
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
                self::processTemplateData(['entity' => $entityType, 'id' => '@test_department->id'])
            )
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET, PATCH, DELETE');
        self::assertResponseContentTypeEquals($response, self::JSON_API_CONTENT_TYPE);
    }

    public function testPatchMethodForListPrimaryResourceRoute()
    {
        $entityType = $this->getEntityType(TestDepartment::class);
        $response = $this->request(
            'PATCH',
            $this->getUrl($this->getListRouteName(), ['entity' => $entityType])
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET, POST, DELETE');
        self::assertResponseContentTypeEquals($response, self::JSON_API_CONTENT_TYPE);
    }

    public function testOptionsMethodForListPrimaryResourceRoute()
    {
        $entityType = $this->getEntityType(TestDepartment::class);
        $response = $this->options($this->getListRouteName(), ['entity' => $entityType]);
        self::assertAllowResponseHeader($response, 'OPTIONS, GET, POST, DELETE');
    }

    public function testHeadMethodForListPrimaryResourceRoute()
    {
        $entityType = $this->getEntityType(TestDepartment::class);
        $response = $this->request(
            'HEAD',
            $this->getUrl($this->getListRouteName(), ['entity' => $entityType])
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET, POST, DELETE');
        self::assertResponseContentTypeEquals($response, self::JSON_API_CONTENT_TYPE);
    }

    public function testOptionsMethodForRelationshipRoute()
    {
        $entityType = $this->getEntityType(TestDepartment::class);
        $response = $this->options(
            $this->getRelationshipRouteName(),
            ['entity' => $entityType, 'id' => '@test_department->id', 'association' => 'staff']
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
                    'id'          => '@test_department->id',
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
            ['entity' => $entityType, 'id' => '@test_department->id', 'association' => 'staff'],
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
            ['entity' => $entityType, 'id' => '@test_department->id', 'association' => 'staff'],
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
            ['entity' => $entityType, 'id' => '@test_department->id', 'association' => 'staff'],
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
            ['entity' => $entityType, 'id' => '@test_department->id', 'association' => 'staff']
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
                    'id'          => '@test_department->id',
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

        $this->assertResponseValidationError(
            [
                'title'  => 'relationship constraint',
                'detail' => 'Unsupported subresource.'
            ],
            $response,
            Response::HTTP_NOT_FOUND
        );
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

        $this->assertResponseValidationError(
            [
                'title'  => 'relationship constraint',
                'detail' => 'Unsupported subresource.'
            ],
            $response,
            Response::HTTP_NOT_FOUND
        );
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

        $this->assertResponseValidationError(
            [
                'title'  => 'relationship constraint',
                'detail' => 'Unsupported subresource.'
            ],
            $response,
            Response::HTTP_NOT_FOUND
        );
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

        $this->assertResponseValidationError(
            [
                'title'  => 'relationship constraint',
                'detail' => 'Unsupported subresource.'
            ],
            $response,
            Response::HTTP_NOT_FOUND
        );
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

        $this->assertResponseValidationError(
            [
                'title'  => 'relationship constraint',
                'detail' => 'Unsupported subresource.'
            ],
            $response,
            Response::HTTP_NOT_FOUND
        );
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

        $this->assertResponseValidationError(
            [
                'title'  => 'relationship constraint',
                'detail' => 'Unsupported subresource.'
            ],
            $response,
            Response::HTTP_NOT_FOUND
        );
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

        $this->assertResponseValidationError(
            [
                'title'  => 'relationship constraint',
                'detail' => 'Unsupported subresource.'
            ],
            $response,
            Response::HTTP_NOT_FOUND
        );
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

        $this->assertResponseValidationError(
            [
                'title'  => 'relationship constraint',
                'detail' => 'Unsupported subresource.'
            ],
            $response,
            Response::HTTP_NOT_FOUND
        );
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

        $this->assertResponseValidationError(
            [
                'title'  => 'relationship constraint',
                'detail' => 'Unsupported subresource.'
            ],
            $response,
            Response::HTTP_NOT_FOUND
        );
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
}
