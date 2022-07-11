<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity\TestDepartment;
use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity\TestEmployee;
use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\FeatureToggleBundle\Tests\Functional\Stub\FeatureCheckerStub;
use Symfony\Component\HttpFoundation\Response;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class DisabledResourceTest extends RestJsonApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([
            '@OroApiBundle/Tests/Functional/DataFixtures/table_inheritance.yml'
        ]);
        $this->client->disableReboot();

        /** @var FeatureCheckerStub $featureChecker */
        $featureChecker = self::getContainer()->get('oro_featuretoggle.checker.feature_checker');
        $featureChecker->setResourceEnabled(TestDepartment::class, 'api_resources', false);
    }

    public function testTryToGetOptionsForList(): void
    {
        $entityType = $this->getEntityType(TestDepartment::class);
        $response = $this->options($this->getListRouteName(), ['entity' => $entityType], [], false);
        self::assertResponseStatusCodeEquals($response, Response::HTTP_NOT_FOUND);
    }

    public function testTryToGetOptionsForItem(): void
    {
        $entityType = $this->getEntityType(TestDepartment::class);
        $response = $this->options(
            $this->getItemRouteName(),
            ['entity' => $entityType, 'id' => '<toString(@test_department->id)>'],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_NOT_FOUND);
    }

    public function testTryToGetOptionsForSubresource(): void
    {
        $entityType = $this->getEntityType(TestDepartment::class);
        $response = $this->options(
            $this->getSubresourceRouteName(),
            ['entity' => $entityType, 'id' => '<toString(@test_department->id)>', 'association' => 'staff'],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_NOT_FOUND);
    }

    public function testTryToGetOptionsForRelationship(): void
    {
        $entityType = $this->getEntityType(TestDepartment::class);
        $response = $this->options(
            $this->getRelationshipRouteName(),
            ['entity' => $entityType, 'id' => '<toString(@test_department->id)>', 'association' => 'staff'],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_NOT_FOUND);
    }

    public function testTryToGetList(): void
    {
        $entityType = $this->getEntityType(TestDepartment::class);
        $response = $this->cget(['entity' => $entityType], [], [], false);
        self::assertResponseStatusCodeEquals($response, Response::HTTP_NOT_FOUND);
    }

    public function testTryToGet(): void
    {
        $entityType = $this->getEntityType(TestDepartment::class);
        $response = $this->get(
            ['entity' => $entityType, 'id' => '<toString(@test_department->id)>'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_NOT_FOUND);
    }

    public function testTryToCreate(): void
    {
        $entityType = $this->getEntityType(TestDepartment::class);
        $response = $this->post(
            ['entity' => $entityType],
            ['data' => ['type' => $entityType, 'attributes' => ['title' => 'New']]],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_NOT_FOUND);
    }

    public function testTryToUpdate(): void
    {
        $entityType = $this->getEntityType(TestDepartment::class);
        $response = $this->patch(
            ['entity' => $entityType, 'id' => '<toString(@test_department->id)>'],
            [
                'data' => [
                    'type'       => $entityType,
                    'id'         => '<toString(@test_department->id)>',
                    'attributes' => ['title' => 'Updated']
                ]
            ],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_NOT_FOUND);
    }

    public function testTryToDelete(): void
    {
        $entityType = $this->getEntityType(TestDepartment::class);
        $response = $this->delete(
            ['entity' => $entityType, 'id' => '<toString(@test_department->id)>'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_NOT_FOUND);
    }

    public function testTryToDeleteList(): void
    {
        $entityType = $this->getEntityType(TestDepartment::class);
        $response = $this->cdelete(
            ['entity' => $entityType],
            ['filter' => ['id' => '<toString(@test_department->id)>']],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_NOT_FOUND);
    }

    public function testTryToGetSubresource(): void
    {
        $entityType = $this->getEntityType(TestDepartment::class);
        $response = $this->getSubresource(
            ['entity' => $entityType, 'id' => '<toString(@test_department->id)>', 'association' => 'staff'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_NOT_FOUND);
    }

    public function testTryToGetRelationship(): void
    {
        $entityType = $this->getEntityType(TestDepartment::class);
        $response = $this->getRelationship(
            ['entity' => $entityType, 'id' => '<toString(@test_department->id)>', 'association' => 'staff'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_NOT_FOUND);
    }

    public function testTryToAddRelationship(): void
    {
        $entityType = $this->getEntityType(TestDepartment::class);
        $associatedEntityType = $this->getEntityType(TestEmployee::class);
        $response = $this->postRelationship(
            ['entity' => $entityType, 'id' => '<toString(@test_department->id)>', 'association' => 'staff'],
            ['data' => [['type' => $associatedEntityType, 'id' => '<toString(@test_employee->id)>']]],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_NOT_FOUND);
    }

    public function testTryToUpdateRelationship(): void
    {
        $entityType = $this->getEntityType(TestDepartment::class);
        $associatedEntityType = $this->getEntityType(TestEmployee::class);
        $response = $this->patchRelationship(
            ['entity' => $entityType, 'id' => '<toString(@test_department->id)>', 'association' => 'staff'],
            ['data' => [['type' => $associatedEntityType, 'id' => '<toString(@test_employee->id)>']]],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_NOT_FOUND);
    }

    public function testTryToDeleteRelationship(): void
    {
        $entityType = $this->getEntityType(TestDepartment::class);
        $associatedEntityType = $this->getEntityType(TestEmployee::class);
        $response = $this->deleteRelationship(
            ['entity' => $entityType, 'id' => '<toString(@test_department->id)>', 'association' => 'staff'],
            ['data' => [['type' => $associatedEntityType, 'id' => '<toString(@test_employee->id)>']]],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_NOT_FOUND);
    }

    public function testTryToGetOptionsForSubresourceForAssociationToDisabledResource(): void
    {
        $entityType = $this->getEntityType(TestEmployee::class);
        $response = $this->options(
            $this->getSubresourceRouteName(),
            ['entity' => $entityType, 'id' => '<toString(@test_employee->id)>', 'association' => 'department'],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_NOT_FOUND);
    }

    public function testTryToGetOptionsForRelationshipForAssociationToDisabledResource(): void
    {
        $entityType = $this->getEntityType(TestEmployee::class);
        $response = $this->options(
            $this->getRelationshipRouteName(),
            ['entity' => $entityType, 'id' => '<toString(@test_employee->id)>', 'association' => 'department'],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_NOT_FOUND);
    }

    public function testTryToGetListForAssociationToDisabledResource(): void
    {
        $entityType = $this->getEntityType(TestEmployee::class);
        $response = $this->cget(['entity' => $entityType]);
        $content = self::jsonToArray($response->getContent());
        self::assertArrayNotHasKey('department', $content['data'][0]['relationships']);
    }

    public function testTryToGetForAssociationToDisabledResource(): void
    {
        $entityType = $this->getEntityType(TestEmployee::class);
        $response = $this->get(['entity' => $entityType, 'id' => '<toString(@test_employee->id)>']);
        $content = self::jsonToArray($response->getContent());
        self::assertArrayNotHasKey('department', $content['data']['relationships']);
    }

    public function testTryToUpdateForAssociationToDisabledResource(): void
    {
        $entityType = $this->getEntityType(TestEmployee::class);
        $associatedEntityType = $this->getEntityType(TestDepartment::class);
        $response = $this->patch(
            ['entity' => $entityType, 'id' => '<toString(@test_employee->id)>'],
            [
                'data' => [
                    'type'          => $entityType,
                    'id'            => '<toString(@test_employee->id)>',
                    'relationships' => [
                        'department' => [
                            'data' => ['type' => $associatedEntityType, 'id' => '<toString(@test_department->id)>']
                        ]
                    ]
                ]
            ],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title'  => 'extra fields constraint',
                'detail' => 'This form should not contain extra fields: "department".'
            ],
            $response
        );
    }

    public function testTryToGetSubresourceForAssociationToDisabledResource(): void
    {
        $entityType = $this->getEntityType(TestEmployee::class);
        $response = $this->getSubresource(
            ['entity' => $entityType, 'id' => '<toString(@test_employee->id)>', 'association' => 'department'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_NOT_FOUND);
    }

    public function testTryToGetRelationshipForAssociationToDisabledResource(): void
    {
        $entityType = $this->getEntityType(TestEmployee::class);
        $response = $this->getRelationship(
            ['entity' => $entityType, 'id' => '<toString(@test_employee->id)>', 'association' => 'department'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_NOT_FOUND);
    }

    public function testTryToUpdateRelationshipForAssociationToDisabledResource(): void
    {
        $entityType = $this->getEntityType(TestEmployee::class);
        $associatedEntityType = $this->getEntityType(TestDepartment::class);
        $response = $this->patchRelationship(
            ['entity' => $entityType, 'id' => '<toString(@test_employee->id)>', 'association' => 'department'],
            ['data' => ['type' => $associatedEntityType, 'id' => '<toString(@test_department->id)>']],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_NOT_FOUND);
    }
}
