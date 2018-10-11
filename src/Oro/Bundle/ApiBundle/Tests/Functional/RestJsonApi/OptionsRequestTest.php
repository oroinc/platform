<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity\TestDepartment;
use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Symfony\Component\HttpFoundation\Response;

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
        self::assertAllowResponseHeader($response, 'OPTIONS, GET, POST, DELETE');
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

    public function testTryOptionsMethodForItemRouteWithInvalidDataTypeOfEntityId()
    {
        $entityType = $this->getEntityType(TestDepartment::class);
        $response = $this->options(
            $this->getItemRouteName(),
            ['entity' => $entityType, 'id' => 'test'],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title'  => 'entity identifier constraint',
                'detail' => 'Expected integer value. Given "test".'
            ],
            $response
        );
    }

    public function testTryOptionsMethodForRelationshipRouteWithInvalidDataTypeOfEntityId()
    {
        $entityType = $this->getEntityType(TestDepartment::class);
        $response = $this->options(
            $this->getRelationshipRouteName(),
            ['entity' => $entityType, 'id' => 'test', 'association' => 'owner'],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title'  => 'entity identifier constraint',
                'detail' => 'Expected integer value. Given "test".'
            ],
            $response
        );
    }

    public function testTryOptionsMethodForSubresourceRouteWithInvalidDataTypeOfEntityId()
    {
        $entityType = $this->getEntityType(TestDepartment::class);
        $response = $this->options(
            $this->getSubresourceRouteName(),
            ['entity' => $entityType, 'id' => 'test', 'association' => 'owner'],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title'  => 'entity identifier constraint',
                'detail' => 'Expected integer value. Given "test".'
            ],
            $response
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
        $this->assertResponseValidationError(
            [
                'title'  => 'relationship constraint',
                'detail' => 'Unsupported subresource.'
            ],
            $response,
            Response::HTTP_NOT_FOUND
        );
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
        $this->assertResponseValidationError(
            [
                'title'  => 'relationship constraint',
                'detail' => 'Unsupported subresource.'
            ],
            $response,
            Response::HTTP_NOT_FOUND
        );
    }
}
