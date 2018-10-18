<?php

namespace Oro\Bundle\SecurityBundle\Tests\Functional\AclQuery;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\SecurityBundle\Tests\Functional\DataFixtures\LoadRolesData;
use Oro\Bundle\SecurityBundle\Tests\Functional\Environment\Entity\TestSecurityPerson;

class QueryWithSubqueriesTest extends AclTestCase
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

    private function getQueryWithSubqueryInWhere(): QueryBuilder
    {
        $subquery = $this->getEntityRepository(TestSecurityPerson::class)
            ->createQueryBuilder('subquery')
            ->select('subquery.name')
            ->where('subquery = p');

        $qb = $this->getEntityRepository(TestSecurityPerson::class)
            ->createQueryBuilder('p');

        return $qb
            ->select('p.name as person')
            ->where($qb->expr()->exists($subquery->getDQL()))
            ->orderBy('person');
    }

    public function testAdminShouldSeeAllData()
    {
        $this->authenticateUser('user_with_admin_role');

        $expectedResult = [
            ['person' => 'person_user_has_all_bu'],
            ['person' => 'person_user_has_first_bu'],
            ['person' => 'person_user_has_first_child1_bu'],
            ['person' => 'person_user_has_first_child1_child_bu'],
            ['person' => 'person_user_has_first_child2_bu'],
            ['person' => 'person_user_has_second_bu'],
            ['person' => 'person_user_has_third_bu'],
            ['person' => 'person_user_with_admin_role']
        ];

        $this->assertQuery(
            $this->getQueryWithSubqueryInWhere(),
            $expectedResult,
            ['checkRootEntity' => false]
        );
    }

    public function testUserWithNoneAccessLevelShouldNotSeeAnyData()
    {
        $this->authenticateUser('user_has_all_bu');

        $expectedResult = [];

        $this->assertQuery(
            $this->getQueryWithSubqueryInWhere(),
            $expectedResult,
            ['checkRootEntity' => false]
        );
    }

    public function testUserWithUserAccessLevelShouldSeeOwnData()
    {
        $this->updateRolePermission('ROLE_SECOND', TestSecurityPerson::class, AccessLevel::BASIC_LEVEL);
        $this->authenticateUser('user_has_first_bu');

        $expectedResult = [
            ['person' => 'person_user_has_first_bu']
        ];

        $this->assertQuery(
            $this->getQueryWithSubqueryInWhere(),
            $expectedResult,
            ['checkRootEntity' => false]
        );
    }

    public function testUserWithBusinessUnitAccessLevelShouldSeeOnlyDataFromAssignedBusinessUnit()
    {
        $this->updateRolePermission('ROLE_SECOND', TestSecurityPerson::class, AccessLevel::LOCAL_LEVEL);
        $this->authenticateUser('user_has_first_bu');

        $expectedResult = [
            ['person' => 'person_user_has_all_bu'],
            ['person' => 'person_user_has_first_bu'],
            ['person' => 'person_user_with_admin_role']
        ];

        $this->assertQuery(
            $this->getQueryWithSubqueryInWhere(),
            $expectedResult,
            ['checkRootEntity' => false]
        );
    }

    public function testUserWithDivisionAccessLevelShouldSeeOnlyDataFromAssignedAndChildBusinessUnits()
    {
        $this->updateRolePermission('ROLE_SECOND', TestSecurityPerson::class, AccessLevel::DEEP_LEVEL);
        $this->authenticateUser('user_has_first_bu');

        $expectedResult = [
            ['person' => 'person_user_has_all_bu'],
            ['person' => 'person_user_has_first_bu'],
            ['person' => 'person_user_has_first_child1_bu'],
            ['person' => 'person_user_has_first_child1_child_bu'],
            ['person' => 'person_user_has_first_child2_bu'],
            ['person' => 'person_user_with_admin_role']
        ];

        $this->assertQuery(
            $this->getQueryWithSubqueryInWhere(),
            $expectedResult,
            ['checkRootEntity' => false]
        );
    }

    public function testUserWithOrganizationAccessLevelShouldSeeAllDataFromOrganization()
    {
        $this->updateRolePermission('ROLE_SECOND', TestSecurityPerson::class, AccessLevel::GLOBAL_LEVEL);
        $this->authenticateUser('user_has_first_bu');

        $expectedResult = [
            ['person' => 'person_user_has_all_bu'],
            ['person' => 'person_user_has_first_bu'],
            ['person' => 'person_user_has_first_child1_bu'],
            ['person' => 'person_user_has_first_child1_child_bu'],
            ['person' => 'person_user_has_first_child2_bu'],
            ['person' => 'person_user_has_second_bu'],
            ['person' => 'person_user_has_third_bu'],
            ['person' => 'person_user_with_admin_role']
        ];

        $this->assertQuery(
            $this->getQueryWithSubqueryInWhere(),
            $expectedResult,
            ['checkRootEntity' => false]
        );
    }
}
