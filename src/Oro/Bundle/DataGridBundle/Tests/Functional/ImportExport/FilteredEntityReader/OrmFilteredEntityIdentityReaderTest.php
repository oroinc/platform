<?php

namespace Oro\Bundle\DataGridBundle\Tests\Functional\ImportExport\FilteredEntityReader;

use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datagrid\Manager;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\ImportExport\FilteredEntityReader\OrmFilteredEntityIdentityReader;
use Oro\Bundle\DataGridBundle\Tests\Functional\DataFixtures\LoadGridViewData;
use Oro\Bundle\DataGridBundle\Tests\Functional\DataFixtures\LoadUserData;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationToken;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Migrations\Data\ORM\LoadAdminUserData;

class OrmFilteredEntityIdentityReaderTest extends WebTestCase
{
    private OrmFilteredEntityIdentityReader $reader;

    protected function setUp(): void
    {
        $this->initClient([], $this->generateWsseAuthHeader());
        $this->setSecurityToken();

        $this->loadFixtures([LoadGridViewData::class]);
        $this->reader = $this->getContainer()
            ->get('oro_datagrid.importexport.filtered_entity.orm_entity_identity_reader');
    }

    protected function tearDown(): void
    {
        $this->getContainer()->get('security.token_storage')
            ->setToken(null);
        parent::tearDown();
    }

    public function testGetIds(): void
    {
        $options = [
            'filteredResultsGrid' => 'users-grid',
            'filteredResultsGridParams' => 'i=2&p=25&s[username]=-1&f[username][value]=simple_user2&f[username][type]=1'
        ];

        $datagrid = $this->getDatagrid($options);

        $ids = $this->reader->getIds($datagrid, User::class, $options);

        $this->assertCount(1, $ids);
        $this->assertEquals([
            $this->getReference(LoadUserData::SIMPLE_USER_2)->getId()
        ], $ids);
    }

    /**
     * @dataProvider getDataForTestWithRestrictions
     */
    public function testGetIdsWithRestrictions(string $username, array $expected): void
    {
        $options = [
            'filteredResultsGrid' => 'users-grid',
            'filteredResultsGridParams' =>
                sprintf('i=2&p=25&s[username]=-1&f[username][value]=%s&f[username][type]=1', $username)
        ];

        $this->setSecurityToken($this->getReference(LoadUserData::SIMPLE_USER_2));

        $datagrid = $this->getDatagrid($options);

        $ids = $this->reader->getIds($datagrid, User::class, $options);

        $this->assertCount(1, $ids);

        $this->assertEquals(sort($expected), sort($ids));
    }

    public function getDataForTestWithRestrictions()
    {
        return [
            'Another users is not visible for current user' => [
                'username' => 'admin',
                'expected' => []
            ],
            'Current customer is visible' => [
                'username' => 'simple_user2',
                'expected' => [LoadUserData::SIMPLE_USER_2]
            ]
        ];
    }

    public function testGetIdsForEmptyGrid(): void
    {
        $options = [
            'filteredResultsGrid' => 'users-grid',
            'filteredResultsGridParams' => 'i=1&p=25&s[username]=-1&f[username][value]=unknown&f[username][type]=1'
        ];

        $datagrid = $this->getDatagrid($options);
        $ids = $this->reader->getIds($datagrid, User::class, $options);

        $this->assertCount(1, $ids);
        $this->assertEquals([0], $ids);
    }

    public function testGetIdsWithoutGridParams(): void
    {
        $options = [
            'filteredResultsGrid' => 'users-grid',
        ];

        $datagrid = $this->getDatagrid($options);

        $ids = $this->reader->getIds($datagrid, User::class, $options);
        $this->assertCount(3, $ids);

        $expectedIds = [
            $this->getReference(LoadUserData::SIMPLE_USER)->getId(),
            $this->getReference(LoadUserData::SIMPLE_USER_2)->getId(),
            $this->getAdminUser()->getId()
        ];
        $this->assertEquals(sort($expectedIds), sort($ids));
    }

    private function setSecurityToken(?User $user = null): void
    {
        $container = $this->getContainer();

        if (null === $user) {
            $user = $this->getAdminUser();
        }

        $token = new OrganizationToken($user->getOrganization(), ['ROLE_ADMINISTRATOR']);
        $token->setUser($user);

        $container->get('security.token_storage')
            ->setToken($token);
    }

    private function getDatagrid(array $options): DatagridInterface
    {
        $name = $options['filteredResultsGrid'];
        $queryString = $options['filteredResultsGridParams'] ?? null;
        parse_str($queryString, $parameters);

        return $this->getDatagridManager()->getDatagrid($name, [ParameterBag::MINIFIED_PARAMETERS => $parameters]);
    }

    private function getDatagridManager(): Manager
    {
        return self::getContainer()->get('oro_datagrid.datagrid.manager');
    }

    private function getAdminUser(): User
    {
        return self::getContainer()->get('doctrine')
            ->getRepository(User::class)
            ->findOneBy(['username' => LoadAdminUserData::DEFAULT_ADMIN_USERNAME]);
    }
}
