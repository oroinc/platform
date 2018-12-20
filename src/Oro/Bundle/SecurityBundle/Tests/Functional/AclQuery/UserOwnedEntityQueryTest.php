<?php

namespace Oro\Bundle\SecurityBundle\Tests\Functional\AclQuery;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\SecurityBundle\Tests\Functional\DataFixtures\LoadRolesData;
use Oro\Bundle\SecurityBundle\Tests\Functional\Environment\Entity\TestSecurityPerson;

class UserOwnedEntityQueryTest extends AclTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->loadFixtures([
            '@OroSecurityBundle/Tests/Functional/DataFixtures/load_test_data.yml',
            LoadRolesData::class
        ]);
    }

    private function getQuery(): QueryBuilder
    {
        return $this->getEntityRepository(TestSecurityPerson::class)
            ->createQueryBuilder('person')
            ->select('person.name')
            ->orderBy('person.name');
    }

    public function testAdminShouldSeeAllData()
    {
        $this->authenticateUser('user_with_admin_role');

        $expectedResult = [
            ['name' => 'person_user_has_all_bu'],
            ['name' => 'person_user_has_first_bu'],
            ['name' => 'person_user_has_first_child1_bu'],
            ['name' => 'person_user_has_first_child1_child_bu'],
            ['name' => 'person_user_has_first_child2_bu'],
            ['name' => 'person_user_has_second_bu'],
            ['name' => 'person_user_has_third_bu'],
            ['name' => 'person_user_with_admin_role']
        ];

        $this->assertQuery($this->getQuery(), $expectedResult);
    }

    /**
     * @dataProvider usersWithNoneAccessLevelDataProvider
     */
    public function testUsersWithNoneAccessLevelShouldNotSeeAnyData($userName)
    {
        $this->authenticateUser($userName);

        $expectedResult = [];

        $this->assertQuery($this->getQuery(), $expectedResult);
    }

    public function usersWithNoneAccessLevelDataProvider(): array
    {
        return [
            ['userName' => 'user_has_all_bu'],
            ['userName' => 'user_has_first_bu'],
            ['userName' => 'user_has_second_bu'],
            ['userName' => 'user_has_third_bu'],
            ['userName' => 'user_has_first_child1_bu'],
            ['userName' => 'user_has_first_child2_bu'],
            ['userName' => 'user_has_first_child1_child_bu']
        ];
    }

    /**
     * @dataProvider usersWithUserAccessLevelDataProvider
     */
    public function testUsersWithUserAccessLevelShouldSeeOwnData($userName, $expectedResult)
    {
        $this->updateRolePermission('ROLE_SECOND', TestSecurityPerson::class, AccessLevel::BASIC_LEVEL);
        $this->authenticateUser($userName);

        $this->assertQuery($this->getQuery(), $expectedResult);
    }

    public function usersWithUserAccessLevelDataProvider(): array
    {
        return [
            'User that owns "person_user_has_all_bu" record' => [
                'userName' => 'user_has_all_bu',
                'expectedResult' => [['name' => 'person_user_has_all_bu']]
            ],
            'User that owns "person_user_has_first_bu" record' => [
                'userName' => 'user_has_first_bu',
                'expectedResult' => [['name' => 'person_user_has_first_bu']]
            ],
            'User that owns "person_user_has_second_bu" record' => [
                'userName' => 'user_has_second_bu',
                'expectedResult' => [['name' => 'person_user_has_second_bu']]
            ],
            'User that owns "person_user_has_third_bu" record' => [
                'userName' => 'user_has_third_bu',
                'expectedResult' => [['name' => 'person_user_has_third_bu']]
            ],
            'User that owns "person_user_has_first_child1_bu" record' => [
                'userName' => 'user_has_first_child1_bu',
                'expectedResult' => [['name' => 'person_user_has_first_child1_bu']]
            ],
            'User that owns "person_user_has_first_child2_bu" record' => [
                'userName' => 'user_has_first_child2_bu',
                'expectedResult' => [['name' => 'person_user_has_first_child2_bu']]
            ],
            'User that owns "person_user_has_first_child1_child_bu" record' => [
                'userName' => 'user_has_first_child1_child_bu',
                'expectedResult' => [['name' => 'person_user_has_first_child1_child_bu']]
            ]
        ];
    }

    /**
     * @dataProvider usersWithBusinessUnitAccessLevelDataProvider
     */
    public function testUsersWithBusinessUnitAccessLevelShouldSeeOnlyDataFromAssignedBusinessUnits(
        $userName,
        $expectedResult
    ) {
        $this->updateRolePermission('ROLE_SECOND', TestSecurityPerson::class, AccessLevel::LOCAL_LEVEL);
        $this->authenticateUser($userName);

        $this->assertQuery($this->getQuery(), $expectedResult);
    }

    public function usersWithBusinessUnitAccessLevelDataProvider(): array
    {
        return [
            'User assigned to all root business units' => [
                'userName' => 'user_has_all_bu',
                'expectedResult' => [
                    ['name' => 'person_user_has_all_bu'],
                    ['name' => 'person_user_has_first_bu'],
                    ['name' => 'person_user_has_second_bu'],
                    ['name' => 'person_user_has_third_bu'],
                    ['name' => 'person_user_with_admin_role']
                ]
            ],
            'User assigned to first BU. Users "user_has_all_bu" and "user_with_admin_role" also assigned to it' => [
                'userName' => 'user_has_first_bu',
                'expectedResult' => [
                    ['name' => 'person_user_has_all_bu'],
                    ['name' => 'person_user_has_first_bu'],
                    ['name' => 'person_user_with_admin_role']
                ]
            ],
            'User assigned to second BU. Users "user_has_all_bu" and "user_with_admin_role" also assigned to it' => [
                'userName' => 'user_has_second_bu',
                'expectedResult' => [
                    ['name' => 'person_user_has_all_bu'],
                    ['name' => 'person_user_has_second_bu'],
                    ['name' => 'person_user_with_admin_role']
                ]
            ],
            'User assigned to third BU. Users "user_has_all_bu" and "user_with_admin_role" also assigned to it' => [
                'userName' => 'user_has_third_bu',
                'expectedResult' => [
                    ['name' => 'person_user_has_all_bu'],
                    ['name' => 'person_user_has_third_bu'],
                    ['name' => 'person_user_with_admin_role']
                ]
            ],
            'User assigned to first child BU. Only this user assigned to this BU.' => [
                'userName' => 'user_has_first_child1_bu',
                'expectedResult' => [
                    ['name' => 'person_user_has_first_child1_bu']
                ]
            ],
            'User assigned to second child BU. Only this user assigned to this BU.' => [
                'userName' => 'user_has_first_child2_bu',
                'expectedResult' => [
                    ['name' => 'person_user_has_first_child2_bu']
                ]
            ],
            'User assigned to child BU of first child BU. Only this user assigned to this BU.' => [
                'userName' => 'user_has_first_child1_child_bu',
                'expectedResult' => [
                    ['name' => 'person_user_has_first_child1_child_bu']
                ]
            ]
        ];
    }

    /**
     * @dataProvider usersWithDivisionAccessLevelDataProvider
     */
    public function testUsersWithDivisionAccessLevelShouldSeeOnlyDataFromAssignedAndChildBusinessUnits(
        $userName,
        $expectedResult
    ) {
        $this->updateRolePermission('ROLE_SECOND', TestSecurityPerson::class, AccessLevel::DEEP_LEVEL);
        $this->authenticateUser($userName);

        $this->assertQuery($this->getQuery(), $expectedResult);
    }

    public function usersWithDivisionAccessLevelDataProvider(): array
    {
        return [
            'User assigned to all root business units should also see data from child business units (all data)' => [
                'userName' => 'user_has_all_bu',
                'expectedResult' => [
                    ['name' => 'person_user_has_all_bu'],
                    ['name' => 'person_user_has_first_bu'],
                    ['name' => 'person_user_has_first_child1_bu'],
                    ['name' => 'person_user_has_first_child1_child_bu'],
                    ['name' => 'person_user_has_first_child2_bu'],
                    ['name' => 'person_user_has_second_bu'],
                    ['name' => 'person_user_has_third_bu'],
                    ['name' => 'person_user_with_admin_role']
                ]
            ],
            'User assigned to first BU sees all data from users assigned to this BU and to child BUs' => [
                'userName' => 'user_has_first_bu',
                'expectedResult' => [
                    ['name' => 'person_user_has_all_bu'],
                    ['name' => 'person_user_has_first_bu'],
                    ['name' => 'person_user_has_first_child1_bu'],
                    ['name' => 'person_user_has_first_child1_child_bu'],
                    ['name' => 'person_user_has_first_child2_bu'],
                    ['name' => 'person_user_with_admin_role']
                ]
            ],
            'User assigned to second BU that have no child' => [
                'userName' => 'user_has_second_bu',
                'expectedResult' => [
                    ['name' => 'person_user_has_all_bu'],
                    ['name' => 'person_user_has_second_bu'],
                    ['name' => 'person_user_with_admin_role']
                ]
            ],
            'User assigned to third BU that have no child' => [
                'userName' => 'user_has_third_bu',
                'expectedResult' => [
                    ['name' => 'person_user_has_all_bu'],
                    ['name' => 'person_user_has_third_bu'],
                    ['name' => 'person_user_with_admin_role']
                ]
            ],
            'User assigned to first child BU that have child BU' => [
                'userName' => 'user_has_first_child1_bu',
                'expectedResult' => [
                    ['name' => 'person_user_has_first_child1_bu'],
                    ['name' => 'person_user_has_first_child1_child_bu']
                ]
            ],
            'User assigned to second child BU that have no child' => [
                'userName' => 'user_has_first_child2_bu',
                'expectedResult' => [
                    ['name' => 'person_user_has_first_child2_bu']
                ]
            ],
            'User assigned to child BU of first child BU that have no child' => [
                'userName' => 'user_has_first_child1_child_bu',
                'expectedResult' => [
                    ['name' => 'person_user_has_first_child1_child_bu']
                ]
            ]
        ];
    }

    /**
     * @dataProvider usersWithOrganizationAccessLevelDataProvider
     */
    public function testUsersWithOrganizationAccessLevelShouldSeeAllDataFromOrganization($userName)
    {
        $this->updateRolePermission('ROLE_SECOND', TestSecurityPerson::class, AccessLevel::GLOBAL_LEVEL);
        $this->authenticateUser($userName);

        $expectedResult = [
            ['name' => 'person_user_has_all_bu'],
            ['name' => 'person_user_has_first_bu'],
            ['name' => 'person_user_has_first_child1_bu'],
            ['name' => 'person_user_has_first_child1_child_bu'],
            ['name' => 'person_user_has_first_child2_bu'],
            ['name' => 'person_user_has_second_bu'],
            ['name' => 'person_user_has_third_bu'],
            ['name' => 'person_user_with_admin_role']
        ];

        $this->assertQuery($this->getQuery(), $expectedResult);
    }

    public function usersWithOrganizationAccessLevelDataProvider(): array
    {
        return [
            ['userName' => 'user_has_all_bu'],
            ['userName' => 'user_has_first_bu'],
            ['userName' => 'user_has_second_bu'],
            ['userName' => 'user_has_third_bu'],
            ['userName' => 'user_has_first_child1_bu'],
            ['userName' => 'user_has_first_child2_bu'],
            ['userName' => 'user_has_first_child1_child_bu']
        ];
    }
}
