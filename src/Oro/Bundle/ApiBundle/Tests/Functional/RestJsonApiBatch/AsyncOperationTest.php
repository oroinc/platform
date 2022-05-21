<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiBatch;

use Oro\Bundle\ApiBundle\Entity\AsyncOperation;
use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\SecurityBundle\Test\Functional\RolePermissionExtension;
use Symfony\Component\HttpFoundation\Response;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class AsyncOperationTest extends RestJsonApiTestCase
{
    use RolePermissionExtension;

    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures(['@OroApiBundle/Tests/Functional/DataFixtures/async_operations.yml']);
    }

    public function testGetFinishedOperation()
    {
        $response = $this->get(
            ['entity' => 'asyncoperations', 'id' => '<toString(@user_operation2->id)>']
        );
        $this->assertResponseContains('get_async_operation_finished.yml', $response);
    }

    public function testGetNotOwnOperationOnSystemAccessLevel()
    {
        $response = $this->get(
            ['entity' => 'asyncoperations', 'id' => '<toString(@subordinate_bu_user_operation->id)>']
        );
        $this->assertResponseContains('get_async_operation_subordinate_bu_user.yml', $response);
    }

    public function testTryToGetNotExistingOperation()
    {
        $response = $this->get(
            ['entity' => 'asyncoperations', 'id' => (string)99999999],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_NOT_FOUND);
    }

    public function testTryToGetList()
    {
        $response = $this->cget(
            ['entity' => 'asyncoperations'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_NOT_FOUND);
    }

    public function testTryToCreate()
    {
        $response = $this->post(
            ['entity' => 'asyncoperations'],
            ['data' => ['type' => 'asyncoperations']],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_NOT_FOUND);
    }

    public function testTryToUpdate()
    {
        $response = $this->patch(
            ['entity' => 'asyncoperations', 'id' => '<toString(@user_operation1->id)>'],
            ['data' => ['type' => 'asyncoperations']],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToDelete()
    {
        $response = $this->delete(
            ['entity' => 'asyncoperations', 'id' => '<toString(@user_operation1->id)>'],
            [],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToDeleteList()
    {
        $response = $this->cdelete(
            ['entity' => 'asyncoperations'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_NOT_FOUND);
    }

    public function testGetOwnOperationOnOrganizationAccessLevel()
    {
        $this->updateRolePermissions(
            'ROLE_ADMINISTRATOR',
            AsyncOperation::class,
            [
                'VIEW'   => AccessLevel::GLOBAL_LEVEL,
                'CREATE' => AccessLevel::NONE_LEVEL
            ]
        );

        $response = $this->get(
            ['entity' => 'asyncoperations', 'id' => '<toString(@user_operation1->id)>']
        );
        $this->assertResponseContains('get_async_operation.yml', $response);
    }

    public function testGetSubordinateBuUserOperationOnOrganizationAccessLevel()
    {
        $this->updateRolePermissions(
            'ROLE_ADMINISTRATOR',
            AsyncOperation::class,
            [
                'VIEW'   => AccessLevel::GLOBAL_LEVEL,
                'CREATE' => AccessLevel::NONE_LEVEL
            ]
        );

        $response = $this->get(
            ['entity' => 'asyncoperations', 'id' => '<toString(@subordinate_bu_user_operation->id)>']
        );
        $this->assertResponseContains('get_async_operation_subordinate_bu_user.yml', $response);
    }

    public function testGetSameBuUserOperationOnOrganizationAccessLevel()
    {
        $this->updateRolePermissions(
            'ROLE_ADMINISTRATOR',
            AsyncOperation::class,
            [
                'VIEW'   => AccessLevel::GLOBAL_LEVEL,
                'CREATE' => AccessLevel::NONE_LEVEL
            ]
        );

        $response = $this->get(
            ['entity' => 'asyncoperations', 'id' => '<toString(@default_bu_user_operation->id)>']
        );
        $this->assertResponseContains('get_async_operation_same_bu_user.yml', $response);
    }

    public function testGetRootBuUserOperationOnOrganizationAccessLevel()
    {
        $this->updateRolePermissions(
            'ROLE_ADMINISTRATOR',
            AsyncOperation::class,
            [
                'VIEW'   => AccessLevel::GLOBAL_LEVEL,
                'CREATE' => AccessLevel::NONE_LEVEL
            ]
        );

        $response = $this->get(
            ['entity' => 'asyncoperations', 'id' => '<toString(@root_bu_user_operation->id)>']
        );
        $this->assertResponseContains('get_async_operation_root_bu_user.yml', $response);
    }

    public function testGetOwnOperationOnDivisionAccessLevel()
    {
        $this->updateRolePermissions(
            'ROLE_ADMINISTRATOR',
            AsyncOperation::class,
            [
                'VIEW'   => AccessLevel::DEEP_LEVEL,
                'CREATE' => AccessLevel::NONE_LEVEL
            ]
        );

        $response = $this->get(
            ['entity' => 'asyncoperations', 'id' => '<toString(@user_operation1->id)>']
        );
        $this->assertResponseContains('get_async_operation.yml', $response);
    }

    public function testGetSubordinateBuUserOperationOnDivisionAccessLevel()
    {
        $this->updateRolePermissions(
            'ROLE_ADMINISTRATOR',
            AsyncOperation::class,
            [
                'VIEW'   => AccessLevel::DEEP_LEVEL,
                'CREATE' => AccessLevel::NONE_LEVEL
            ]
        );

        $response = $this->get(
            ['entity' => 'asyncoperations', 'id' => '<toString(@subordinate_bu_user_operation->id)>']
        );
        $this->assertResponseContains('get_async_operation_subordinate_bu_user.yml', $response);
    }

    public function testGetSameBuUserOperationOnDivisionAccessLevel()
    {
        $this->updateRolePermissions(
            'ROLE_ADMINISTRATOR',
            AsyncOperation::class,
            [
                'VIEW'   => AccessLevel::DEEP_LEVEL,
                'CREATE' => AccessLevel::NONE_LEVEL
            ]
        );

        $response = $this->get(
            ['entity' => 'asyncoperations', 'id' => '<toString(@default_bu_user_operation->id)>']
        );
        $this->assertResponseContains('get_async_operation_same_bu_user.yml', $response);
    }

    public function testTryToGetRootBuUserOperationOnDivisionAccessLevel()
    {
        $this->updateRolePermissions(
            'ROLE_ADMINISTRATOR',
            AsyncOperation::class,
            [
                'VIEW'   => AccessLevel::DEEP_LEVEL,
                'CREATE' => AccessLevel::NONE_LEVEL
            ]
        );

        $response = $this->get(
            ['entity' => 'asyncoperations', 'id' => '<toString(@root_bu_user_operation->id)>'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_FORBIDDEN);
    }

    public function testGetOwnOperationOnBUAccessLevel()
    {
        $this->updateRolePermissions(
            'ROLE_ADMINISTRATOR',
            AsyncOperation::class,
            [
                'VIEW'   => AccessLevel::LOCAL_LEVEL,
                'CREATE' => AccessLevel::NONE_LEVEL
            ]
        );

        $response = $this->get(
            ['entity' => 'asyncoperations', 'id' => '<toString(@user_operation1->id)>']
        );
        $this->assertResponseContains('get_async_operation.yml', $response);
    }

    public function testTryToGetSubordinateBuUserOperationOnBUAccessLevel()
    {
        $this->updateRolePermissions(
            'ROLE_ADMINISTRATOR',
            AsyncOperation::class,
            [
                'VIEW'   => AccessLevel::LOCAL_LEVEL,
                'CREATE' => AccessLevel::NONE_LEVEL
            ]
        );

        $response = $this->get(
            ['entity' => 'asyncoperations', 'id' => '<toString(@subordinate_bu_user_operation->id)>'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_FORBIDDEN);
    }

    public function testGetSameBuUserOperationOnBUAccessLevel()
    {
        $this->updateRolePermissions(
            'ROLE_ADMINISTRATOR',
            AsyncOperation::class,
            [
                'VIEW'   => AccessLevel::LOCAL_LEVEL,
                'CREATE' => AccessLevel::NONE_LEVEL
            ]
        );

        $response = $this->get(
            ['entity' => 'asyncoperations', 'id' => '<toString(@default_bu_user_operation->id)>']
        );
        $this->assertResponseContains('get_async_operation_same_bu_user.yml', $response);
    }

    public function testTryToGetRootBuUserOperationOnBUAccessLevel()
    {
        $this->updateRolePermissions(
            'ROLE_ADMINISTRATOR',
            AsyncOperation::class,
            [
                'VIEW'   => AccessLevel::LOCAL_LEVEL,
                'CREATE' => AccessLevel::NONE_LEVEL
            ]
        );

        $response = $this->get(
            ['entity' => 'asyncoperations', 'id' => '<toString(@root_bu_user_operation->id)>'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_FORBIDDEN);
    }

    public function testGetOwnOperationOnUserAccessLevel()
    {
        $this->updateRolePermissions(
            'ROLE_ADMINISTRATOR',
            AsyncOperation::class,
            [
                'VIEW'   => AccessLevel::BASIC_LEVEL,
                'CREATE' => AccessLevel::NONE_LEVEL
            ]
        );

        $response = $this->get(
            ['entity' => 'asyncoperations', 'id' => '<toString(@user_operation1->id)>']
        );
        $this->assertResponseContains('get_async_operation.yml', $response);
    }

    public function testTryToGetSubordinateBuUserOperationOnUserAccessLevel()
    {
        $this->updateRolePermissions(
            'ROLE_ADMINISTRATOR',
            AsyncOperation::class,
            [
                'VIEW'   => AccessLevel::BASIC_LEVEL,
                'CREATE' => AccessLevel::NONE_LEVEL
            ]
        );

        $response = $this->get(
            ['entity' => 'asyncoperations', 'id' => '<toString(@subordinate_bu_user_operation->id)>'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_FORBIDDEN);
    }

    public function testTryToGetSameBuUserOperationOnUserAccessLevel()
    {
        $this->updateRolePermissions(
            'ROLE_ADMINISTRATOR',
            AsyncOperation::class,
            [
                'VIEW'   => AccessLevel::BASIC_LEVEL,
                'CREATE' => AccessLevel::NONE_LEVEL
            ]
        );

        $response = $this->get(
            ['entity' => 'asyncoperations', 'id' => '<toString(@default_bu_user_operation->id)>'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_FORBIDDEN);
    }

    public function testTryToGetRootBuUserOperationOnUserAccessLevel()
    {
        $this->updateRolePermissions(
            'ROLE_ADMINISTRATOR',
            AsyncOperation::class,
            [
                'VIEW'   => AccessLevel::BASIC_LEVEL,
                'CREATE' => AccessLevel::NONE_LEVEL
            ]
        );

        $response = $this->get(
            ['entity' => 'asyncoperations', 'id' => '<toString(@root_bu_user_operation->id)>'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_FORBIDDEN);
    }

    public function testTryToGetOwnOperationOnNoneAccessLevel()
    {
        $this->updateRolePermissions(
            'ROLE_ADMINISTRATOR',
            AsyncOperation::class,
            [
                'VIEW'   => AccessLevel::NONE_LEVEL,
                'CREATE' => AccessLevel::NONE_LEVEL
            ]
        );

        $response = $this->get(
            ['entity' => 'asyncoperations', 'id' => '<toString(@user_operation1->id)>'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_FORBIDDEN);
    }

    public function testTryToGetSubordinateBuUserOperationOnNoneAccessLevel()
    {
        $this->updateRolePermissions(
            'ROLE_ADMINISTRATOR',
            AsyncOperation::class,
            [
                'VIEW'   => AccessLevel::NONE_LEVEL,
                'CREATE' => AccessLevel::NONE_LEVEL
            ]
        );

        $response = $this->get(
            ['entity' => 'asyncoperations', 'id' => '<toString(@subordinate_bu_user_operation->id)>'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_FORBIDDEN);
    }

    public function testTryToGetSameBuUserOperationOnNoneAccessLevel()
    {
        $this->updateRolePermissions(
            'ROLE_ADMINISTRATOR',
            AsyncOperation::class,
            [
                'VIEW'   => AccessLevel::NONE_LEVEL,
                'CREATE' => AccessLevel::NONE_LEVEL
            ]
        );

        $response = $this->get(
            ['entity' => 'asyncoperations', 'id' => '<toString(@default_bu_user_operation->id)>'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_FORBIDDEN);
    }

    public function testTryToGetRootBuUserOperationOnNoneAccessLevel()
    {
        $this->updateRolePermissions(
            'ROLE_ADMINISTRATOR',
            AsyncOperation::class,
            [
                'VIEW'   => AccessLevel::NONE_LEVEL,
                'CREATE' => AccessLevel::NONE_LEVEL
            ]
        );

        $response = $this->get(
            ['entity' => 'asyncoperations', 'id' => '<toString(@root_bu_user_operation->id)>'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_FORBIDDEN);
    }
}
