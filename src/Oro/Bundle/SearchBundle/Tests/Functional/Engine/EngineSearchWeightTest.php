<?php

namespace Oro\Bundle\SearchBundle\Tests\Functional\Engine;

use Doctrine\Common\Collections\Expr\Expression;
use Oro\Bundle\SearchBundle\Engine\IndexerInterface;
use Oro\Bundle\SearchBundle\Event\PrepareEntityMapEvent;
use Oro\Bundle\SearchBundle\Query\Criteria\Criteria;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\SearchBundle\Tests\Functional\Controller\DataFixtures\LoadSearchItemData;
use Oro\Bundle\SearchBundle\Tests\Functional\Controller\SearchBundleWebTestCase;
use Oro\Bundle\TestFrameworkBundle\Entity\Item;

/**
 * @group search
 * @dbIsolationPerTest
 */
class EngineSearchWeightTest extends SearchBundleWebTestCase
{
    /** @var callable */
    private $listener;

    protected function setUp()
    {
        parent::setUp();

        $this->initClient([], $this->generateBasicAuthHeader());

        $alias = $this->getSearchObjectMapper()->getEntityAlias(Item::class);
        $this->getSearchIndexer()->resetIndex(Item::class);
        $this->ensureItemsLoaded($alias, 0);
    }

    protected function tearDown()
    {
        if ($this->listener) {
            $this->getContainer()->get('event_dispatcher')
                ->removeListener(PrepareEntityMapEvent::EVENT_NAME, $this->listener);
        }

        parent::tearDown();
    }

    /**
     * @param array $expectedItems
     * @param callable $listener
     * @param Expression|null $condition
     * @param array $orderings
     *
     * @dataProvider searchWeightDataProvider
     */
    public function testSearchWeight(
        array $expectedItems,
        $listener,
        Expression $condition = null,
        array $orderings = []
    ) {
        $this->listener = $listener;
        $this->getContainer()->get('event_dispatcher')->addListener(
            PrepareEntityMapEvent::EVENT_NAME,
            $this->listener,
            -255
        );

        $alias = $this->getSearchObjectMapper()->getEntityAlias(Item::class);
        $this->loadFixtures([LoadSearchItemData::class]);
        $this->getSearchIndexer()->reindex(Item::class);
        $this->ensureItemsLoaded($alias, LoadSearchItemData::COUNT);

        $query = new Query();
        $query->from($alias);
        if ($condition) {
            $query->getCriteria()->andWhere($condition);
        }
        if ($orderings) {
            $query->getCriteria()->orderBy($orderings);
        }

        $expectedIds = [];
        foreach ($expectedItems as $reference) {
            /** @var Item $item */
            $item = $this->getReference($reference);
            $expectedIds[] = $item->getId();
        }

        $actualItems = $this->getContainer()->get('oro_search.search.engine')->search($query)->getValues();
        $actualIds = [];
        /** @var \Oro\Bundle\SearchBundle\Query\Result\Item $resultItem */
        foreach ($actualItems as $resultItem) {
            $actualIds[] = (integer)$resultItem->getRecordId();
        }

        $this->assertEquals($expectedIds, $actualIds);
    }

    /**
     * @return array
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function searchWeightDataProvider()
    {
        return [
            'ascending without fulltext search without orderings' => [
                'expected items' => [
                    'item_1',
                    'item_2',
                    'item_3',
                    'item_4',
                    'item_5',
                    'item_6',
                    'item_7',
                    'item_8',
                    'item_9',
                ],
                'listener' => function (PrepareEntityMapEvent $event) {
                    $data = $event->getData();
                    $data[Query::TYPE_DECIMAL][IndexerInterface::WEIGHT_FIELD]
                        = 1 / $data[Query::TYPE_DECIMAL]['decimalValue'];
                    $event->setData($data);
                },
            ],
            'descending without fulltext search without orderings' => [
                'expected items' => [
                    'item_9',
                    'item_8',
                    'item_7',
                    'item_6',
                    'item_5',
                    'item_4',
                    'item_3',
                    'item_2',
                    'item_1',
                ],
                'listener' => function (PrepareEntityMapEvent $event) {
                    $data = $event->getData();
                    $data[Query::TYPE_DECIMAL][IndexerInterface::WEIGHT_FIELD]
                        = $data[Query::TYPE_DECIMAL]['decimalValue'];
                    $event->setData($data);
                },
            ],
            'ascending with fulltext search without orderings' => [
                'expected items' => [
                    'item_1',
                    'item_2',
                    'item_3',
                    'item_4',
                    'item_6',
                    'item_7',
                    'item_8',
                    'item_9',
                ],
                'listener' => function (PrepareEntityMapEvent $event) {
                    $data = $event->getData();
                    $data[Query::TYPE_DECIMAL][IndexerInterface::WEIGHT_FIELD]
                        = 1 / $data[Query::TYPE_DECIMAL]['decimalValue'];
                    $event->setData($data);
                },
                'condition' => Criteria::expr()->notContains('text.stringValue', 'item5'),
            ],
            'descending with fulltext search without orderings' => [
                'expected items' => [
                    'item_9',
                    'item_8',
                    'item_7',
                    'item_6',
                    'item_4',
                    'item_3',
                    'item_2',
                    'item_1',
                ],
                'listener' => function (PrepareEntityMapEvent $event) {
                    $data = $event->getData();
                    $data[Query::TYPE_DECIMAL][IndexerInterface::WEIGHT_FIELD]
                        = $data[Query::TYPE_DECIMAL]['decimalValue'];
                    $event->setData($data);
                },
                'condition' => Criteria::expr()->notContains('text.stringValue', 'item5'),
            ],
            'without fulltext search with orderings' => [
                // weight is generated for descending values, but actual results are ascending
                // because if sorting is specified then search relevance weight is ignored
                'expected items' => [
                    'item_1',
                    'item_2',
                    'item_3',
                    'item_4',
                    'item_5',
                    'item_6',
                    'item_7',
                    'item_8',
                    'item_9',
                ],
                'listener' => function (PrepareEntityMapEvent $event) {
                    $data = $event->getData();
                    $data[Query::TYPE_DECIMAL][IndexerInterface::WEIGHT_FIELD]
                        = $data[Query::TYPE_DECIMAL]['decimalValue'];
                    $event->setData($data);
                },
                'condition' => null,
                'orderings' => ['integer.integerValue' => 'ASC']
            ],
            'with fulltext search with orderings' => [
                // weight is generated for descending values, but actual results are ascending
                // because if sorting is specified then search relevance weight is ignored
                'expected items' => [
                    'item_1',
                    'item_2',
                    'item_3',
                    'item_4',
                    'item_6',
                    'item_7',
                    'item_8',
                    'item_9',
                ],
                'listener' => function (PrepareEntityMapEvent $event) {
                    $data = $event->getData();
                    $data[Query::TYPE_DECIMAL][IndexerInterface::WEIGHT_FIELD]
                        = $data[Query::TYPE_DECIMAL]['decimalValue'];
                    $event->setData($data);
                },
                'condition' => Criteria::expr()->notContains('text.stringValue', 'item5'),
                'orderings' => ['integer.integerValue' => 'ASC']
            ],
        ];
    }
}
