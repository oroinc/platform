<?php

namespace Oro\Bundle\SecurityBundle\Tests\Functional\AclQuery;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\SecurityBundle\Tests\Functional\DataFixtures\LoadRolesData;
use Oro\Bundle\SecurityBundle\Tests\Functional\Environment\Entity\TestSecurityDepartment;
use Oro\Bundle\SecurityBundle\Tests\Functional\Environment\Entity\TestSecurityPerson;

class QueryWithJoinsTest extends AclTestCase
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
            ->createQueryBuilder('p')
            ->select('p.name as person', 'o.username as owner', 'd.name as department')
            ->join('p.owner', 'o')
            ->leftJoin('p.department', 'd')
            ->orderBy('person');
    }

    public function testAdminShouldSeeAllData()
    {
        $this->authenticateUser('user_with_admin_role');

        $expectedResult = [
            [
                'person' => 'person_user_has_all_bu',
                'owner' => 'user_has_all_bu',
                'department' => 'department_bu_first_child1_child'
            ],
            [
                'person' => 'person_user_has_first_bu',
                'owner' => 'user_has_first_bu',
                'department' => 'department_bu_first_child2'
            ],
            [
                'person' => 'person_user_has_first_child1_bu',
                'owner' => 'user_has_first_child1_bu',
                'department' => 'department_bu_second'
            ],
            [
                'person' => 'person_user_has_first_child1_child_bu',
                'owner' => 'user_has_first_child1_child_bu',
                'department' => 'department_bu_first'
            ],
            [
                'person' => 'person_user_has_first_child2_bu',
                'owner' => 'user_has_first_child2_bu',
                'department' => 'department_bu_first'
            ],
            [
                'person' => 'person_user_has_second_bu',
                'owner' => 'user_has_second_bu',
                'department' => 'department_bu_first_child1'
            ],
            [
                'person' => 'person_user_has_third_bu',
                'owner' => 'user_has_third_bu',
                'department' => 'department_bu_third'
            ],
            [
                'person' => 'person_user_with_admin_role',
                'owner' => 'user_with_admin_role',
                'department' => 'department_bu_first'
            ]
        ];

        $this->assertQuery($this->getQuery(), $expectedResult);
    }

    public function testUserWithNoneAccessLevelShouldNotSeeAnyData()
    {
        $this->authenticateUser('user_has_all_bu');

        $expectedResult = [];

        $this->assertQuery($this->getQuery(), $expectedResult);
    }

    public function testUserWithNoAccessAndDisabledCheckRelationsAndDisabledCheckRootEntity()
    {
        $this->authenticateUser('user_has_all_bu');

        $expectedResult = [
            [
                'person' => 'person_user_has_all_bu',
                'owner' => 'user_has_all_bu',
                'department' => 'department_bu_first_child1_child'
            ],
            [
                'person' => 'person_user_has_first_bu',
                'owner' => 'user_has_first_bu',
                'department' => 'department_bu_first_child2'
            ],
            [
                'person' => 'person_user_has_first_child1_bu',
                'owner' => 'user_has_first_child1_bu',
                'department' => 'department_bu_second'
            ],
            [
                'person' => 'person_user_has_first_child1_child_bu',
                'owner' => 'user_has_first_child1_child_bu',
                'department' => 'department_bu_first'
            ],
            [
                'person' => 'person_user_has_first_child2_bu',
                'owner' => 'user_has_first_child2_bu',
                'department' => 'department_bu_first'
            ],
            [
                'person' => 'person_user_has_second_bu',
                'owner' => 'user_has_second_bu',
                'department' => 'department_bu_first_child1'
            ],
            [
                'person' => 'person_user_has_third_bu',
                'owner' => 'user_has_third_bu',
                'department' => 'department_bu_third'
            ],
            [
                'person' => 'person_user_with_admin_role',
                'owner' => 'user_with_admin_role',
                'department' => 'department_bu_first'
            ]
        ];

        $this->assertQuery(
            $this->getQuery(),
            $expectedResult,
            ['checkRelations' => false, 'checkRootEntity' => false]
        );
    }

    public function testUserWithUserAccessLevelToPersonEntityOnlyAndDisabledOwnerCheck()
    {
        $this->updateRolePermission('ROLE_SECOND', TestSecurityPerson::class, AccessLevel::BASIC_LEVEL);
        $this->authenticateUser('user_has_all_bu');

        $expectedResult = [[
            'person' => 'person_user_has_all_bu',
            'owner' => 'user_has_all_bu', // user should see owner information even if he has no access to user entity
            'department' => null
        ]];

        $this->assertQuery($this->getQuery(), $expectedResult);
    }

    public function testUserWithUserAccessLevelToPersonEntityOnlyAndEnabledOwnerCheck()
    {
        $this->updateRolePermission('ROLE_SECOND', TestSecurityPerson::class, AccessLevel::BASIC_LEVEL);
        $this->authenticateUser('user_has_all_bu');

        // user should not see any data because he has no access to view User entities and join to this entity is inner
        $expectedResult = [];

        $this->assertQuery(
            $this->getQuery(),
            $expectedResult,
            ['aclCheckOwner' => true]
        );
    }

    public function testUserWithUserAccessLevelToPersonEntityOnlyAndDisabledCheckRelations()
    {
        $this->updateRolePermission('ROLE_SECOND', TestSecurityPerson::class, AccessLevel::BASIC_LEVEL);
        $this->authenticateUser('user_has_all_bu');

        $expectedResult = [[
            'person' => 'person_user_has_all_bu',
            'owner' => 'user_has_all_bu',
            'department' => 'department_bu_first_child1_child'
        ]];

        $this->assertQuery(
            $this->getQuery(),
            $expectedResult,
            ['checkRelations' => false]
        );
    }

    public function testUseWithBusinessUnitAccessLevelToPersonAndDepartments()
    {
        $this->updateRolePermission('ROLE_SECOND', TestSecurityPerson::class, AccessLevel::LOCAL_LEVEL);
        $this->updateRolePermission('ROLE_SECOND', TestSecurityDepartment::class, AccessLevel::LOCAL_LEVEL);
        $this->authenticateUser('user_has_all_bu');

        $expectedResult = [
            [
                'person' => 'person_user_has_all_bu',
                'owner' => 'user_has_all_bu',
                'department' => null // 'department_bu_first_child1_child' department is not available
            ],
            [
                'person' => 'person_user_has_first_bu',
                'owner' => 'user_has_first_bu',
                'department' => null // 'department_bu_first_child2' department is not available
            ],
            [
                'person' => 'person_user_has_second_bu',
                'owner' => 'user_has_second_bu',
                'department' => null // 'department_bu_first_child1' department is not available
            ],
            [
                'person' => 'person_user_has_third_bu',
                'owner' => 'user_has_third_bu',
                'department' => 'department_bu_third'
            ],
            [
                'person' => 'person_user_with_admin_role',
                'owner' => 'user_with_admin_role',
                'department' => 'department_bu_first'
            ]
        ];

        $this->assertQuery($this->getQuery(), $expectedResult);
    }
}
