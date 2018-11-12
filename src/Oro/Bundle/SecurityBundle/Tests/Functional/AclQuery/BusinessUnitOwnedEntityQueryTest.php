<?php

namespace Oro\Bundle\SecurityBundle\Tests\Functional\AclQuery;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\SecurityBundle\Tests\Functional\DataFixtures\LoadRolesData;
use Oro\Bundle\SecurityBundle\Tests\Functional\Environment\Entity\TestSecurityDepartment;

class BusinessUnitOwnedEntityQueryTest extends AclTestCase
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
        return $this->getEntityRepository(TestSecurityDepartment::class)
            ->createQueryBuilder('department')
            ->select('department.name')
            ->orderBy('department.name');
    }

    public function testAdminShouldSeeAllData()
    {
        $this->authenticateUser('user_with_admin_role');

        $expectedResult = [
            ['name' => 'department_bu_first'],
            ['name' => 'department_bu_first_child1'],
            ['name' => 'department_bu_first_child1_child'],
            ['name' => 'department_bu_first_child2'],
            ['name' => 'department_bu_second'],
            ['name' => 'department_bu_third']
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
     * @dataProvider usersWithBusinessUnitAccessLevelDataProvider
     */
    public function testUsersWithBusinessUnitAccessLevelShouldSeeOnlyDataFromAssignedBusinessUnits(
        $userName,
        $expectedResult
    ) {
        $this->updateRolePermission('ROLE_SECOND', TestSecurityDepartment::class, AccessLevel::LOCAL_LEVEL);
        $this->authenticateUser($userName);

        $this->assertQuery($this->getQuery(), $expectedResult);
    }

    public function usersWithBusinessUnitAccessLevelDataProvider(): array
    {
        return [
            'User assigned to all root business units' => [
                'userName' => 'user_has_all_bu',
                'expectedResult' => [
                    ['name' => 'department_bu_first'],
                    ['name' => 'department_bu_second'],
                    ['name' => 'department_bu_third']
                ]
            ],
            'User assigned to first BU. At this BU was created only one record' => [
                'userName' => 'user_has_first_bu',
                'expectedResult' => [
                    ['name' => 'department_bu_first']
                ]
            ],
            'User assigned to second BU. At this BU was created only one record' => [
                'userName' => 'user_has_second_bu',
                'expectedResult' => [
                    ['name' => 'department_bu_second']
                ]
            ],
            'User assigned to third BU. At this BU was created only one record' => [
                'userName' => 'user_has_third_bu',
                'expectedResult' => [
                    ['name' => 'department_bu_third']
                ]
            ],
            'User assigned to first child BU. At this BU was created only one record' => [
                'userName' => 'user_has_first_child1_bu',
                'expectedResult' => [
                    ['name' => 'department_bu_first_child1']
                ]
            ],
            'User assigned to second child BU. At this BU was created only one record' => [
                'userName' => 'user_has_first_child2_bu',
                'expectedResult' => [
                    ['name' => 'department_bu_first_child2']
                ]
            ],
            'User assigned to child BU of first child BU. At this BU was created only one record' => [
                'userName' => 'user_has_first_child1_child_bu',
                'expectedResult' => [
                    ['name' => 'department_bu_first_child1_child']
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
        $this->updateRolePermission('ROLE_SECOND', TestSecurityDepartment::class, AccessLevel::DEEP_LEVEL);
        $this->authenticateUser($userName);

        $this->assertQuery($this->getQuery(), $expectedResult);
    }

    public function usersWithDivisionAccessLevelDataProvider(): array
    {
        return [
            'User assigned to all root business units should also see data from child business units (all data)' => [
                'userName' => 'user_has_all_bu',
                'expectedResult' => [
                    ['name' => 'department_bu_first'],
                    ['name' => 'department_bu_first_child1'],
                    ['name' => 'department_bu_first_child1_child'],
                    ['name' => 'department_bu_first_child2'],
                    ['name' => 'department_bu_second'],
                    ['name' => 'department_bu_third']
                ]
            ],
            'User assigned to first BU sees all data his BU and from child BUs' => [
                'userName' => 'user_has_first_bu',
                'expectedResult' => [
                    ['name' => 'department_bu_first'],
                    ['name' => 'department_bu_first_child1'],
                    ['name' => 'department_bu_first_child1_child'],
                    ['name' => 'department_bu_first_child2']
                ]
            ],
            'User assigned to second BU that have no child' => [
                'userName' => 'user_has_second_bu',
                'expectedResult' => [
                    ['name' => 'department_bu_second']
                ]
            ],
            'User assigned to third BU that have no child' => [
                'userName' => 'user_has_third_bu',
                'expectedResult' => [
                    ['name' => 'department_bu_third']
                ]
            ],
            'User assigned to first child BU that have child BU' => [
                'userName' => 'user_has_first_child1_bu',
                'expectedResult' => [
                    ['name' => 'department_bu_first_child1'],
                    ['name' => 'department_bu_first_child1_child']
                ]
            ],
            'User assigned to second child BU that have no child' => [
                'userName' => 'user_has_first_child2_bu',
                'expectedResult' => [
                    ['name' => 'department_bu_first_child2']
                ]
            ],
            'User assigned to child BU of first child BU that have no child' => [
                'userName' => 'user_has_first_child1_child_bu',
                'expectedResult' => [
                    ['name' => 'department_bu_first_child1_child']
                ]
            ]
        ];
    }

    /**
     * @dataProvider usersWithOrganizationAccessLevelDataProvider
     */
    public function testUsersWithOrganizationAccessLevelShouldSeeAllDataFromOrganization($userName)
    {
        $this->updateRolePermission('ROLE_SECOND', TestSecurityDepartment::class, AccessLevel::GLOBAL_LEVEL);
        $this->authenticateUser($userName);

        $expectedResult = [
            ['name' => 'department_bu_first'],
            ['name' => 'department_bu_first_child1'],
            ['name' => 'department_bu_first_child1_child'],
            ['name' => 'department_bu_first_child2'],
            ['name' => 'department_bu_second'],
            ['name' => 'department_bu_third']
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
