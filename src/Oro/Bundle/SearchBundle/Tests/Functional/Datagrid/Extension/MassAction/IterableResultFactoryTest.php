<?php

namespace Oro\Bundle\SearchBundle\Tests\Functional\Datagrid\Extension\MassAction;

use Oro\Bundle\DataGridBundle\Datagrid\Manager;
use Oro\Bundle\DataGridBundle\Datasource\Orm\IterableResultInterface;
use Oro\Bundle\DataGridBundle\Exception\LogicException;
use Oro\Bundle\DataGridBundle\Extension\Action\ActionConfiguration;
use Oro\Bundle\DataGridBundle\Extension\MassAction\DTO\SelectedItems;
use Oro\Bundle\DataGridBundle\Extension\MassAction\IterableResultFactoryInterface;
use Oro\Bundle\SearchBundle\Tests\Functional\Controller\DataFixtures\LoadSearchItemData;
use Oro\Bundle\SearchBundle\Tests\Functional\Controller\SearchBundleWebTestCase;
use Oro\Bundle\TestFrameworkBundle\Entity\Item;

/**
 * @group search
 * @dbIsolationPerTest
 */
class IterableResultFactoryTest extends SearchBundleWebTestCase
{
    const GRID_NAME = 'test-search-grid';

    protected function setUp()
    {
        $this->initClient();

        if (static::getContainer()->getParameter('oro_search.engine') !== 'orm') {
            $this->markTestSkipped('Should be tested only with ORM search engine');
        }

        $alias = $this->getSearchObjectMapper()->getEntityAlias(Item::class);
        $this->getSearchIndexer()->resetIndex(Item::class);
        $this->ensureItemsLoaded($alias, 0);

        $this->loadFixtures([LoadSearchItemData::class]);
        $this->getSearchIndexer()->reindex(Item::class);
        $this->ensureItemsLoaded($alias, LoadSearchItemData::COUNT);
    }

    public function testCreateIterableResultWithoutIdentifierField()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Mass action must define identifier name');

        $datagrid = $this->getDatagridManager()->getDatagrid(self::GRID_NAME);
        $selectedItems = SelectedItems::createFromParameters([]);

        $this->getFactory()->createIterableResult(
            $datagrid->getAcceptedDatasource(),
            ActionConfiguration::create([]),
            $datagrid->getConfig(),
            $selectedItems
        );
    }

    public function testCreateIterableResultWhenAllItemsExceptSeveralSelected()
    {
        $datagrid = $this->getDatagridManager()->getDatagrid(self::GRID_NAME);

        /** @var Item $firstItem */
        $firstItem = $this->getReference('item_1');
        /** @var Item $secondItem */
        $secondItem = $this->getReference('item_2');
        /** @var Item $thirdItem */
        $thirdItem = $this->getReference('item_3');
        /** @var Item $sixthItem */
        $sixthItem = $this->getReference('item_6');

        $selectedItems = SelectedItems::createFromParameters([
            'values' => [
                $firstItem->getId(),
                $secondItem->getId(),
                $thirdItem->getId(),
                $sixthItem->getId()
            ],
            'inset' => false
        ]);

        $iterableResult = $this->getFactory()->createIterableResult(
            $datagrid->getAcceptedDatasource(),
            ActionConfiguration::create(['data_identifier' => 'integer.id']),
            $datagrid->getConfig(),
            $selectedItems
        );

        $this->assertRecordIds([
            $this->getReference('item_4')->getId(),
            $this->getReference('item_5')->getId(),
            $this->getReference('item_7')->getId(),
            $this->getReference('item_8')->getId(),
            $this->getReference('item_9')->getId()
        ], $iterableResult);
    }

    public function testCreateIterableResultWhenSeveralItemsSelected()
    {
        $datagrid = $this->getDatagridManager()->getDatagrid(self::GRID_NAME);

        /** @var Item $firstItem */
        $firstItem = $this->getReference('item_1');
        /** @var Item $seventhItem */
        $seventhItem = $this->getReference('item_7');

        $selectedItems = SelectedItems::createFromParameters([
            'values' => [
                $firstItem->getId(),
                $seventhItem->getId()
            ],
            'inset' => true
        ]);

        $iterableResult = $this->getFactory()->createIterableResult(
            $datagrid->getAcceptedDatasource(),
            ActionConfiguration::create(['data_identifier' => 'integer.id']),
            $datagrid->getConfig(),
            $selectedItems
        );

        $this->assertRecordIds([$firstItem->getId(), $seventhItem->getId()], $iterableResult);
    }

    /**
     * @param array $expectedRecordTitles
     * @param IterableResultInterface $result
     */
    private function assertRecordIds(array $expectedRecordTitles, IterableResultInterface $result)
    {
        $items = iterator_to_array($result);
        $recordTitles = array_map(function (\Oro\Bundle\SearchBundle\Query\Result\Item $item) {
            return $item->getRecordId();
        }, $items);

        $this->assertEquals($expectedRecordTitles, $recordTitles);
    }

    /**
     * @return IterableResultFactoryInterface
     */
    private function getFactory()
    {
        return $this->client->getContainer()->get('oro_search.extension.mass_action.iterable_result_factory.alias');
    }

    /**
     * @return Manager
     */
    private function getDatagridManager()
    {
        return $this->client->getContainer()->get('oro_datagrid.datagrid.manager');
    }
}
