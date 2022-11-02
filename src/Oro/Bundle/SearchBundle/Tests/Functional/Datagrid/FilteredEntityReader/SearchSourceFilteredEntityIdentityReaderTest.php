<?php

namespace Oro\Bundle\SearchBundle\Tests\Functional\Datagrid\FilteredEntityReader;

use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datagrid\Manager;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SearchBundle\Datagrid\FilteredEntityReader\SearchSourceFilteredEntityIdentityReader;
use Oro\Bundle\SearchBundle\Tests\Functional\Controller\DataFixtures\LoadSearchItemData;
use Oro\Bundle\SearchBundle\Tests\Functional\Controller\SearchBundleWebTestCase;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationToken;
use Oro\Bundle\TestFrameworkBundle\Entity\Item;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * @dbIsolationPerTest
 */
class SearchSourceFilteredEntityIdentityReaderTest extends SearchBundleWebTestCase
{
    private const GRID_NAME = 'test-search-grid';

    protected SearchSourceFilteredEntityIdentityReader $reader;

    protected function setUp(): void
    {
        $this->initClient();

        $this->loadFixture(Item::class, LoadSearchItemData::class, LoadSearchItemData::COUNT);
        $this->reader = $this->getContainer()
            ->get('oro_search.importexport.filtered_entity.search_entity_identity_reader');
    }

    public function testGetIds(): void
    {
        $options = [
            'filteredResultsGrid' => self::GRID_NAME,
            'filteredResultsGridParams' => 'i=1&p=25&f[stringValue][value]=item1@mail.com&f[stringValue][type]=3'
        ];

        $datagrid = $this->getDatagrid($options);
        $ids = $this->reader->getIds($datagrid, Item::class, $options);

        $this->assertCount(1, $ids);
        $expectedValues = [
            $this->getReference('item_1')->getId()
        ];
        $this->assertEquals($expectedValues, $ids);
    }

    public function testGetIdsForEmptyGrid(): void
    {
        $options = [
            'filteredResultsGrid' => self::GRID_NAME,
            'filteredResultsGridParams' => 'i=2&p=25&f[stringValue][value]=item0@mail.com&f[stringValue][type]=3'
        ];

        $datagrid = $this->getDatagrid($options);
        $ids = $this->reader->getIds($datagrid, Item::class, $options);

        $this->assertCount(1, $ids);
        $this->assertEquals([0], $ids);
    }

    public function testGetIdsWithoutGridParameters()
    {
        $options = [
            'filteredResultsGrid' => self::GRID_NAME,
            'filteredResultsGridParams' => null
        ];

        $datagrid = $this->getDatagrid($options);
        $ids = $this->reader->getIds($datagrid, Item::class, $options);

        $this->assertCount(9, $ids);
        $expectedValues = [
            $this->getReference('item_1')->getId(),
            $this->getReference('item_2')->getId(),
            $this->getReference('item_3')->getId(),
            $this->getReference('item_4')->getId(),
            $this->getReference('item_5')->getId(),
            $this->getReference('item_6')->getId(),
            $this->getReference('item_7')->getId(),
            $this->getReference('item_8')->getId(),
            $this->getReference('item_9')->getId()
        ];
        $this->assertEquals($expectedValues, $ids);
    }

    /**
     * Loading fixtures could not be used in this case because of overriden startTransaction method.
     * @see SearchBundleWebTestCase::startTransaction()
     */
    public function testGetIdsWithRestrictions()
    {
        // Create token with fake organization which Id is another than organization which used in fixtures.
        $user = new User();
        $organizationId = $this->getReference('organization')->getId();
        $organization = new Organization();
        $organization->setId(++$organizationId);
        $token = new OrganizationToken($organization, []);
        $token->setUser($user);
        self::getContainer()->get('security.token_storage')
            ->setToken($token);

        $options = [
            'filteredResultsGrid' => self::GRID_NAME,
            'filteredResultsGridParams' => 'i=1&p=25&f[stringValue][value]=item1@mail.com&f[stringValue][type]=3'
        ];

        $datagrid = $this->getDatagrid($options);
        $ids = $this->reader->getIds($datagrid, Item::class, $options);

        // Grid ids result should be empty as another organization context used.
        $this->assertCount(1, $ids);
        $this->assertEquals([0], $ids);

        self::getContainer()->get('security.token_storage')
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
        return $this->client->getContainer()->get('oro_datagrid.datagrid.manager');
    }
}
