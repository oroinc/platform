<?php

namespace Oro\Bundle\UserBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\UserBundle\Entity\Role;
use Oro\Bundle\UserBundle\Tests\Functional\Api\DataFixtures\LoadRoleData;
use Symfony\Component\HttpFoundation\Response;

/**
 * @dbIsolationPerTest
 */
class RoleTest extends RestJsonApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([LoadRoleData::class]);
    }

    public function testGetList(): void
    {
        $response = $this->cget(
            ['entity' => 'userroles'],
            ['filter[role]' => 'ROLE_2']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type'          => 'userroles',
                        'id'            => '<toString(@role2->id)>',
                        'attributes'    => [
                            'role'  => 'ROLE_2',
                            'label' => 'Role 2'
                        ],
                        'relationships' => [
                            'users' => [
                                'data' => [['type' => 'users', 'id' => '<toString(@user->id)>']]
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testGet(): void
    {
        $response = $this->get(
            ['entity' => 'userroles', 'id' => '<toString(@role1->id)>']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    'type'          => 'userroles',
                    'id'            => '<toString(@role1->id)>',
                    'attributes'    => [
                        'role'  => 'ROLE_1',
                        'label' => 'Role 1'
                    ],
                    'relationships' => [
                        'users' => [
                            'data' => [['type' => 'users', 'id' => '<toString(@user->id)>']]
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testDelete(): void
    {
        $roleId = $this->getReference('role3')->getId();

        $this->delete(
            ['entity' => 'userroles', 'id' => (string)$roleId]
        );

        $deletedRole = $this->getEntityManager()->find(Role::class, $roleId);
        self::assertTrue(null === $deletedRole);
    }

    public function testTryToDeleteWhenAssignedToSomeUsers(): void
    {
        $response = $this->delete(
            ['entity' => 'userroles', 'id' => '<toString(@role2->id)>'],
            [],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title'  => 'access denied exception',
                'detail' => 'The delete operation is forbidden. Reason: has users.'
            ],
            $response,
            Response::HTTP_FORBIDDEN
        );
    }

    public function testDeleteList(): void
    {
        $roleId = $this->getReference('role3')->getId();

        $this->cdelete(
            ['entity' => 'userroles'],
            ['filter[role]' => 'ROLE_3']
        );

        $deletedRole = $this->getEntityManager()->find(Role::class, $roleId);
        self::assertTrue(null === $deletedRole);
    }

    public function testCreate(): void
    {
        $data = [
            'data' => [
                'type'       => 'userroles',
                'attributes' => [
                    'label' => 'New Role'
                ]
            ]
        ];
        $response = $this->post(
            ['entity' => 'userroles'],
            $data
        );

        $this->assertResponseContains($data, $response);

        $role = $this->getEntityManager()->find(Role::class, $this->getResourceId($response));
        self::assertNotNull($role);
        self::assertEquals('New Role', $role->getLabel());
    }

    public function testUpdate(): void
    {
        $roleId = $this->getReference('role1')->getId();

        $data = [
            'data' => [
                'type'       => 'userroles',
                'id'         => (string)$roleId,
                'attributes' => [
                    'label' => 'Updated Role'
                ]
            ]
        ];
        $response = $this->patch(
            ['entity' => 'userroles', 'id' => (string)$roleId],
            $data
        );

        $role = $this->getEntityManager()->find(Role::class, $this->getResourceId($response));
        self::assertNotNull($role);
        self::assertEquals('Updated Role', $role->getLabel());
    }

    public function testTryToCreateRoleWithoutLabel(): void
    {
        $data = [
            'data' => [
                'type' => 'userroles'
            ]
        ];
        $response = $this->post(
            ['entity' => 'userroles'],
            $data,
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title'  => 'not blank constraint',
                'detail' => 'This value should not be blank.',
                'source' => ['pointer' => '/data/attributes/label']
            ],
            $response
        );
    }

    public function testTryToSetRoleLabelToNull(): void
    {
        $roleId = $this->getReference('role1')->getId();

        $data = [
            'data' => [
                'type'       => 'userroles',
                'id'         => (string)$roleId,
                'attributes' => [
                    'label' => null
                ]
            ]
        ];
        $response = $this->patch(
            ['entity' => 'userroles', 'id' => (string)$roleId],
            $data,
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title'  => 'not blank constraint',
                'detail' => 'This value should not be blank.',
                'source' => ['pointer' => '/data/attributes/label']
            ],
            $response
        );
    }
}
