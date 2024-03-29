<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity\TestDepartment;
use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class OptionsRequestTest extends RestJsonApiTestCase
{
    public function testOptionsMethodForItemRoute()
    {
        $entityType = $this->getEntityType(TestDepartment::class);
        $response = $this->options(
            $this->getItemRouteName(),
            ['entity' => $entityType, 'id' => '9999']
        );
        self::assertAllowResponseHeader($response, 'OPTIONS, GET, PATCH, DELETE');
    }

    public function testOptionsMethodForListRoute()
    {
        $entityType = $this->getEntityType(TestDepartment::class);
        $response = $this->options(
            $this->getListRouteName(),
            ['entity' => $entityType]
        );
        self::assertAllowResponseHeader($response, 'OPTIONS, GET, PATCH, POST, DELETE');
    }

    public function testOptionsMethodForToOneRelationshipRoute()
    {
        $entityType = $this->getEntityType(TestDepartment::class);
        $response = $this->options(
            $this->getRelationshipRouteName(),
            ['entity' => $entityType, 'id' => '9999', 'association' => 'owner']
        );
        self::assertAllowResponseHeader($response, 'OPTIONS, GET, PATCH');
    }

    public function testOptionsMethodForToManyRelationshipRoute()
    {
        $entityType = $this->getEntityType(TestDepartment::class);
        $response = $this->options(
            $this->getRelationshipRouteName(),
            ['entity' => $entityType, 'id' => '9999', 'association' => 'staff']
        );
        self::assertAllowResponseHeader($response, 'OPTIONS, GET, PATCH, POST, DELETE');
    }

    public function testOptionsMethodForToOneSubresourceRoute()
    {
        $entityType = $this->getEntityType(TestDepartment::class);
        $response = $this->options(
            $this->getSubresourceRouteName(),
            ['entity' => $entityType, 'id' => '9999', 'association' => 'owner']
        );
        self::assertAllowResponseHeader($response, 'OPTIONS, GET');
    }

    public function testOptionsMethodForToManySubresourceRoute()
    {
        $entityType = $this->getEntityType(TestDepartment::class);
        $response = $this->options(
            $this->getSubresourceRouteName(),
            ['entity' => $entityType, 'id' => '9999', 'association' => 'staff']
        );
        self::assertAllowResponseHeader($response, 'OPTIONS, GET');
    }

    public function testOptionsMethodForItemRouteWithInvalidDataTypeOfEntityId()
    {
        $entityType = $this->getEntityType(TestDepartment::class);
        $response = $this->options(
            $this->getItemRouteName(),
            ['entity' => $entityType, 'id' => 'test']
        );
        self::assertAllowResponseHeader($response, 'OPTIONS, GET, PATCH, DELETE');
    }

    public function testOptionsMethodForRelationshipRouteWithInvalidDataTypeOfEntityId()
    {
        $entityType = $this->getEntityType(TestDepartment::class);
        $response = $this->options(
            $this->getRelationshipRouteName(),
            ['entity' => $entityType, 'id' => 'test', 'association' => 'owner']
        );
        self::assertAllowResponseHeader($response, 'OPTIONS, GET, PATCH');
    }

    public function testOptionsMethodForSubresourceRouteWithInvalidDataTypeOfEntityId()
    {
        $entityType = $this->getEntityType(TestDepartment::class);
        $response = $this->options(
            $this->getSubresourceRouteName(),
            ['entity' => $entityType, 'id' => 'test', 'association' => 'owner']
        );
        self::assertAllowResponseHeader($response, 'OPTIONS, GET');
    }

    public function testTryOptionsMethodForItemRouteWithUnknownEntityType()
    {
        $response = $this->options(
            $this->getItemRouteName(),
            ['entity' => 'unknown', 'id' => '9999'],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title'  => 'entity type constraint',
                'detail' => 'Unknown entity type: unknown.'
            ],
            $response,
            Response::HTTP_NOT_FOUND
        );
    }

    public function testTryOptionsMethodForListRouteWithUnknownEntityType()
    {
        $response = $this->options(
            $this->getListRouteName(),
            ['entity' => 'unknown'],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title'  => 'entity type constraint',
                'detail' => 'Unknown entity type: unknown.'
            ],
            $response,
            Response::HTTP_NOT_FOUND
        );
    }

    public function testTryOptionsMethodForRelationshipRouteWithUnknownEntityType()
    {
        $response = $this->options(
            $this->getRelationshipRouteName(),
            ['entity' => 'unknown', 'id' => '9999', 'association' => 'owner'],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title'  => 'entity type constraint',
                'detail' => 'Unknown parent entity type: unknown.'
            ],
            $response,
            Response::HTTP_NOT_FOUND
        );
    }

    public function testTryOptionsMethodForSubresourceRouteWithUnknownEntityType()
    {
        $response = $this->options(
            $this->getSubresourceRouteName(),
            ['entity' => 'unknown', 'id' => '9999', 'association' => 'owner'],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title'  => 'entity type constraint',
                'detail' => 'Unknown parent entity type: unknown.'
            ],
            $response,
            Response::HTTP_NOT_FOUND
        );
    }

    public function testTryOptionsMethodForRelationshipRouteWithUnknownAssociation()
    {
        $entityType = $this->getEntityType(TestDepartment::class);
        $response = $this->options(
            $this->getRelationshipRouteName(),
            ['entity' => $entityType, 'id' => '9999', 'association' => 'unknown'],
            [],
            false
        );
        $this->assertUnsupportedSubresourceResponse($response);
    }

    public function testTryOptionsMethodForSubresourceRouteWithUnknownAssociation()
    {
        $entityType = $this->getEntityType(TestDepartment::class);
        $response = $this->options(
            $this->getSubresourceRouteName(),
            ['entity' => $entityType, 'id' => '9999', 'association' => 'unknown'],
            [],
            false
        );
        $this->assertUnsupportedSubresourceResponse($response);
    }
}
