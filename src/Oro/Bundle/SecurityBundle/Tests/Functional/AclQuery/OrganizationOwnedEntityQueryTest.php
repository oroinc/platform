<?php

namespace Oro\Bundle\SecurityBundle\Tests\Functional\AclQuery;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\SecurityBundle\Tests\Functional\DataFixtures\LoadRolesData;
use Oro\Bundle\SecurityBundle\Tests\Functional\Environment\Entity\TestSecurityCompany;

class OrganizationOwnedEntityQueryTest extends AclTestCase
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
        return $this->getEntityRepository(TestSecurityCompany::class)
            ->createQueryBuilder('company')
            ->select('company.name')
            ->orderBy('company.name');
    }

    public function testAdminShouldSeeAllData()
    {
        $this->authenticateUser('user_with_admin_role');

        $expectedResult = [
            ['name' => 'company_first']
        ];

        $this->assertQuery($this->getQuery(), $expectedResult);
    }

    /**
     * @dataProvider usersProvider
     */
    public function testUsersWithNoneAccessLevelShouldNotSeeAnyData($userName)
    {
        $this->authenticateUser($userName);

        $expectedResult = [];

        $this->assertQuery($this->getQuery(), $expectedResult);
    }

    /**
     * @dataProvider usersProvider
     */
    public function testUsersWithOrganizationAccessLevelShouldSeeAllRecordsFromOrganization($userName)
    {
        $this->updateRolePermission('ROLE_SECOND', TestSecurityCompany::class, AccessLevel::GLOBAL_LEVEL);
        $this->authenticateUser($userName);

        $expectedResult = [
            ['name' => 'company_first']
        ];

        $this->assertQuery($this->getQuery(), $expectedResult);
    }

    public function usersProvider(): array
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
