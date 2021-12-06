<?php

namespace Oro\Bundle\DataGridBundle\Tests\Functional\ImportExport;

use Oro\Bundle\DataGridBundle\ImportExport\FilteredEntityReader;
use Oro\Bundle\DataGridBundle\Tests\Functional\DataFixtures\LoadGridViewData;
use Oro\Bundle\DataGridBundle\Tests\Functional\DataFixtures\LoadUserData;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationToken;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Migrations\Data\ORM\LoadAdminUserData;

class FilteredEntityReaderTest extends WebTestCase
{
    /** @var FilteredEntityReader */
    private $reader;

    protected function setUp(): void
    {
        $this->initClient([], $this->generateWsseAuthHeader());
        $this->setSecurityToken();

        $this->loadFixtures([LoadGridViewData::class]);
        $this->reader = $this->getContainer()->get('oro_datagrid.importexport.export_filtered_reader');
    }

    protected function tearDown(): void
    {
        $this->getContainer()->get('security.token_storage')
            ->setToken(null);
    }

    private function setSecurityToken(): void
    {
        $container = $this->getContainer();

        /** @var User $user */
        $user = $container->get('doctrine')
            ->getRepository(User::class)
            ->findOneBy([]);

        $token = new OrganizationToken($user->getOrganization(), ['ROLE_ADMINISTRATOR']);
        $token->setUser($user);

        $container->get('security.token_storage')
            ->setToken($token);
    }

    public function testGetIds(): void
    {
        $ids = $this->reader->getIds(User::class, [
            'filteredResultsGrid' => 'users-grid',
            'filteredResultsGridParams' => 'i=2&p=25&s[username]=-1&f[username][value]=admin&f[username][type]=1'
        ]);

        $this->assertCount(1, $ids);
    }

    public function testGetIdsWithoutIdentityReaders(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Applicable entity identity reader is not found');

        $reader = clone $this->reader;
        $reflectionClass = new \ReflectionClass(get_class($reader));
        $method = $reflectionClass->getProperty('entityIdentityReaders');
        $method->setAccessible(true);
        $method->setValue($reader, []);

        $reader->getIds(User::class, [
            'filteredResultsGrid' => 'users-grid',
            'filteredResultsGridParams' => 'i=1&p=25&s[username]=-1&f[username][value]=admin&f[username][type]=1'
        ]);
    }

    public function testGetIdsForEmptyGrid(): void
    {
        $ids = $this->reader->getIds(User::class, [
            'filteredResultsGrid' => 'users-grid',
            'filteredResultsGridParams' => 'i=1&p=25&s[username]=-1&f[username][value]=unknown&f[username][type]=1'
        ]);

        $this->assertCount(1, $ids);
        $this->assertEquals([0], $ids);
    }

    public function testGetIdsWithNotExistingDatagrid(): void
    {
        $ids = $this->reader->getIds(User::class, [
            'filteredResultsGrid' => 'test-datagrid',
            'filteredResultsGridParams' => 'i=1&p=25&s[username]=-1&f[username][value]=admin&f[username][type]=1'
        ]);

        $this->assertCount(1, $ids);
        $this->assertEquals([0], $ids);
    }

    public function testGetIdsWithIncorrectGridParams(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('filteredResultsGridParams parameter should be of string type, array given.');

        $ids = $this->reader->getIds(User::class, [
            'filteredResultsGrid' => 'users-grid',
            'filteredResultsGridParams' => ['key' => 'value']
        ]);

        $this->assertCount(1, $ids);
        $this->assertEquals([0], $ids);
    }

    public function testGetIdsWithoutGridParams(): void
    {
        $ids = $this->reader->getIds(User::class, [
            'filteredResultsGrid' => 'users-grid'
        ]);

        $this->assertCount(3, $ids);
    }

    public function testGetIdsWithoutGridOptions(): void
    {
        $ids = $this->reader->getIds(User::class, []);

        $this->assertCount(3, $ids);

        $expectedIds = [
            $this->getReference(LoadUserData::SIMPLE_USER)->getId(),
            $this->getReference(LoadUserData::SIMPLE_USER_2)->getId(),
            $this->getAdminUser()->getId()
        ];
        $this->assertEqualsCanonicalizing($expectedIds, $ids);
    }

    public function testInternalReadMethodCalled(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Reader must be configured with source');

        $this->reader->read();
    }

    private function getAdminUser(): User
    {
        return self::getContainer()->get('doctrine')
            ->getRepository(User::class)
            ->findOneBy(['username' => LoadAdminUserData::DEFAULT_ADMIN_USERNAME]);
    }
}
