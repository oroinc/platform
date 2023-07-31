<?php

namespace Oro\Bundle\UserBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\UserBundle\Entity\Role;
use Oro\Bundle\UserBundle\Tests\Functional\Api\DataFixtures\LoadUserRoleData;
use Symfony\Component\HttpFoundation\Response;

/**
 * @dbIsolationPerTest
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class UserRoleTest extends RestJsonApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([LoadUserRoleData::class]);
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
        $response = $this->post(['entity' => 'userroles'], $data);

        $this->assertResponseContains($data, $response);

        $role = $this->getEntityManager()->find(Role::class, $this->getResourceId($response));
        self::assertNotNull($role);
        self::assertEquals('New Role', $role->getLabel());
    }

    public function testCreateWithCode(): void
    {
        $data = [
            'data' => [
                'type'       => 'userroles',
                'attributes' => [
                    'role'  => 'new 1',
                    'label' => 'New Role'
                ]
            ]
        ];
        $response = $this->post(['entity' => 'userroles'], $data);

        $responseContext = self::jsonToArray($response->getContent());
        $roleCode = $responseContext['data']['attributes']['role'];
        self::assertStringStartsWith(Role::PREFIX_ROLE . 'NEW_1_', $roleCode);

        $expectedData = $data;
        $expectedData['data']['attributes']['role'] = $roleCode;
        $this->assertResponseContains($expectedData, $response);

        $role = $this->getEntityManager()->find(Role::class, $this->getResourceId($response));
        self::assertNotNull($role);
        self::assertEquals($roleCode, $role->getRole());
        self::assertEquals('New Role', $role->getLabel());
    }

    public function testCreateWithPrefixedCode(): void
    {
        $data = [
            'data' => [
                'type'       => 'userroles',
                'attributes' => [
                    'role'  => Role::PREFIX_ROLE . 'NEW_1',
                    'label' => 'New Role'
                ]
            ]
        ];
        $response = $this->post(['entity' => 'userroles'], $data);

        $responseContext = self::jsonToArray($response->getContent());
        $roleCode = $responseContext['data']['attributes']['role'];
        self::assertStringStartsWith(Role::PREFIX_ROLE . 'NEW_1_', $roleCode);

        $expectedData = $data;
        $expectedData['data']['attributes']['role'] = $roleCode;
        $this->assertResponseContains($expectedData, $response);

        $role = $this->getEntityManager()->find(Role::class, $this->getResourceId($response));
        self::assertNotNull($role);
        self::assertEquals($roleCode, $role->getRole());
        self::assertEquals('New Role', $role->getLabel());
    }

    public function testTryToCreateWithTooLongCode(): void
    {
        $data = [
            'data' => [
                'type'       => 'userroles',
                'attributes' => [
                    'role'  => 'ROLE_678901234567890',
                    'label' => 'New Role'
                ]
            ]
        ];
        $response = $this->post(['entity' => 'userroles'], $data, [], false);

        $this->assertResponseValidationError(
            [
                'title'  => 'length constraint',
                'detail' => 'This value is too long. It should have 30 characters or less.',
                'source' => ['pointer' => '/data/attributes/role']
            ],
            $response
        );
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
        $response = $this->patch(['entity' => 'userroles', 'id' => (string)$roleId], $data);

        $role = $this->getEntityManager()->find(Role::class, $this->getResourceId($response));
        self::assertNotNull($role);
        self::assertEquals('Updated Role', $role->getLabel());
    }

    public function testTryToCreateWithoutLabel(): void
    {
        $data = [
            'data' => [
                'type' => 'userroles'
            ]
        ];
        $response = $this->post(['entity' => 'userroles'], $data, [], false);

        $this->assertResponseValidationError(
            [
                'title'  => 'not blank constraint',
                'detail' => 'This value should not be blank.',
                'source' => ['pointer' => '/data/attributes/label']
            ],
            $response
        );
    }

    public function testTryToSetLabelToNull(): void
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
        $response = $this->patch(['entity' => 'userroles', 'id' => (string)$roleId], $data, [], false);

        $this->assertResponseValidationError(
            [
                'title'  => 'not blank constraint',
                'detail' => 'This value should not be blank.',
                'source' => ['pointer' => '/data/attributes/label']
            ],
            $response
        );
    }

    public function testTryToSetCodeToNull(): void
    {
        $roleId = $this->getReference('role1')->getId();

        $data = [
            'data' => [
                'type'       => 'userroles',
                'id'         => (string)$roleId,
                'attributes' => [
                    'role' => null
                ]
            ]
        ];
        $response = $this->patch(['entity' => 'userroles', 'id' => (string)$roleId], $data, [], false);

        $this->assertResponseValidationError(
            [
                'title'  => 'not blank constraint',
                'detail' => 'This value should not be blank.',
                'source' => ['pointer' => '/data/attributes/role']
            ],
            $response
        );
    }

    public function testChangeCode(): void
    {
        $roleId = $this->getReference('role1')->getId();

        $data = [
            'data' => [
                'type'       => 'userroles',
                'id'         => (string)$roleId,
                'attributes' => [
                    'role' => 'UPDATED1'
                ]
            ]
        ];
        $response = $this->patch(['entity' => 'userroles', 'id' => (string)$roleId], $data);

        $role = $this->getEntityManager()->find(Role::class, $this->getResourceId($response));
        self::assertNotNull($role);
        self::assertStringStartsWith(Role::PREFIX_ROLE . 'UPDATED1_', $role->getRole());
    }
}
