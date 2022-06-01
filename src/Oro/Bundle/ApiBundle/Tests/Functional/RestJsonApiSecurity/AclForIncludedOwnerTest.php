<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiSecurity;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\SecurityBundle\Test\Functional\RolePermissionExtension;
use Oro\Bundle\TestFrameworkBundle\Entity\TestEntityWithUserOwnership;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadUser;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\HttpFoundation\Response;

class AclForIncludedOwnerTest extends RestJsonApiTestCase
{
    use RolePermissionExtension;

    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([
            '@OroApiBundle/Tests/Functional/DataFixtures/test_entity_with_user_ownership.yml',
            LoadUser::class
        ]);
    }

    public function testTryToGetTestEntityForUserWithoutPermissionsToUserEntity()
    {
        $this->updateRolePermissions(
            'ROLE_ADMINISTRATOR',
            User::class,
            [
                'VIEW'   => AccessLevel::NONE_LEVEL,
                'CREATE' => AccessLevel::NONE_LEVEL,
                'DELETE' => AccessLevel::NONE_LEVEL,
                'ASSIGN' => AccessLevel::NONE_LEVEL,
                'EDIT'   => AccessLevel::NONE_LEVEL
            ]
        );

        $response = $this->get(
            ['entity' => 'testentitywithuserownerships', 'id' => '<toString(@record1->id)>'],
            ['include' => 'owner']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    'type'          => 'testentitywithuserownerships',
                    'id'            => '<toString(@record1->id)>',
                    'relationships' => ['owner' => ['data' => null]]
                ]
            ],
            $response
        );
    }

    public function testTryToGetTestEntityForUserWithoutPermissionsToMainEntity()
    {
        $this->updateRolePermissions(
            'ROLE_ADMINISTRATOR',
            TestEntityWithUserOwnership::class,
            [
                'VIEW'   => AccessLevel::NONE_LEVEL,
                'CREATE' => AccessLevel::NONE_LEVEL,
                'DELETE' => AccessLevel::NONE_LEVEL,
                'ASSIGN' => AccessLevel::NONE_LEVEL,
                'EDIT'   => AccessLevel::NONE_LEVEL
            ]
        );

        $response = $this->get(
            ['entity' => 'testentitywithuserownerships', 'id' => '<toString(@record1->id)>'],
            ['include' => 'owner'],
            [],
            false
        );

        self::assertResponseStatusCodeEquals($response, Response::HTTP_FORBIDDEN);
    }

    public function testTryToGetTestEntityForUserWithFullPermissions()
    {
        $this->updateRolePermissions(
            'ROLE_ADMINISTRATOR',
            User::class,
            [
                'VIEW'   => AccessLevel::SYSTEM_LEVEL,
                'CREATE' => AccessLevel::SYSTEM_LEVEL,
                'DELETE' => AccessLevel::SYSTEM_LEVEL,
                'ASSIGN' => AccessLevel::SYSTEM_LEVEL,
                'EDIT'   => AccessLevel::SYSTEM_LEVEL
            ]
        );
        $this->updateRolePermissions(
            'ROLE_ADMINISTRATOR',
            TestEntityWithUserOwnership::class,
            [
                'VIEW'   => AccessLevel::SYSTEM_LEVEL,
                'CREATE' => AccessLevel::SYSTEM_LEVEL,
                'DELETE' => AccessLevel::SYSTEM_LEVEL,
                'ASSIGN' => AccessLevel::SYSTEM_LEVEL,
                'EDIT'   => AccessLevel::SYSTEM_LEVEL
            ]
        );

        $response = $this->get(
            ['entity' => 'testentitywithuserownerships', 'id' => '<toString(@record1->id)>'],
            ['include' => 'owner']
        );

        $this->assertResponseContains(
            [
                'data'     => [
                    'type'          => 'testentitywithuserownerships',
                    'id'            => '<toString(@record1->id)>',
                    'relationships' => ['owner' => ['data' => ['type' => 'users', 'id' => '<toString(@user->id)>']]]
                ],
                'included' => [['type' => 'users', 'id' => '<toString(@user->id)>']]
            ],
            $response
        );
    }
}
