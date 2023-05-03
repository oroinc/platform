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
 */
class IterableResultFactoryTest extends SearchBundleWebTestCase
{
    private const GRID_NAME = 'test-search-grid';

    protected function setUp(): void
    {
        $this->initClient();

        $engine = self::getContainer()->get('oro_search.engine.parameters')->getEngineName();
        if ($engine !== 'orm') {
            $this->markTestSkipped('Should be tested only with ORM search engine');
        }

        $this->loadFixture(Item::class, LoadSearchItemData::class, LoadSearchItemData::COUNT);
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

    private function assertRecordIds(array $expectedRecordIds, IterableResultInterface $result)
    {
        $items = iterator_to_array($result);
        $recordIds = array_map(function (\Oro\Bundle\SearchBundle\Query\Result\Item $item) {
            return $item->getRecordId();
        }, $items);

        $this->assertEquals($expectedRecordIds, $recordIds);
    }

    private function getFactory(): IterableResultFactoryInterface
    {
        return $this->client->getContainer()->get('oro_search.extension.mass_action.iterable_result_factory.alias');
    }

    private function getDatagridManager(): Manager
    {
        return $this->client->getContainer()->get('oro_datagrid.datagrid.manager');
    }
}
