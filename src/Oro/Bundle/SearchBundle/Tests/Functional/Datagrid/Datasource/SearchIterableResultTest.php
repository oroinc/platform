<?php

namespace Oro\Bundle\SearchBundle\Tests\Functional\Datagrid\Datasource;

use Oro\Bundle\SearchBundle\Datagrid\Datasource\SearchIterableResult;
use Oro\Bundle\SearchBundle\Query\Criteria\Criteria;
use Oro\Bundle\SearchBundle\Query\IndexerQuery;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\SearchBundle\Tests\Functional\Controller\DataFixtures\LoadSearchItemData;
use Oro\Bundle\SearchBundle\Tests\Functional\Controller\SearchBundleWebTestCase;
use Oro\Bundle\TestFrameworkBundle\Entity\Item;

/**
 * @group search
 * @dbIsolationPerTest
 */
class SearchIterableResultTest extends SearchBundleWebTestCase
{
    private $expectedRecordTitles = [
        'item1@mail.com 0123-456100',
        'item2@mail.com 200987654',
        'item3@mail.com 0123-456300',
        'item4@mail.com 400987654',
        'item5@mail.com 0123-456500',
        'item6@mail.com 600987654',
        'item7@mail.com 0123-456700',
        'item8@mail.com 800987654',
        'item9@mail.com 0123-456900'
    ];

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

    public function testWithDefaultBufferSize()
    {
        $searchQuery = new Query();
        $alias = $this->getSearchObjectMapper()->getEntityAlias(Item::class);
        $searchQuery->from($alias)->getCriteria()->orderBy(['stringValue' => Criteria::ASC]);

        $result = new SearchIterableResult(
            new IndexerQuery(self::getContainer()->get('oro_search.index'), $searchQuery)
        );

        $this->assertAllRecordTitles($result);
    }

    public function testWithSmallBufferSize()
    {
        $searchQuery = new Query();
        $alias = $this->getSearchObjectMapper()->getEntityAlias(Item::class);
        $searchQuery->from($alias)->getCriteria()->orderBy(['stringValue' => Criteria::ASC]);

        $result = new SearchIterableResult(
            new IndexerQuery(self::getContainer()->get('oro_search.index'), $searchQuery)
        );

        $result->setBufferSize(2);

        $this->assertAllRecordTitles($result);
    }

    /**
     * @param SearchIterableResult $result
     */
    private function assertAllRecordTitles(SearchIterableResult $result)
    {
        $items = iterator_to_array($result);
        $recordTitles = array_map(function (\Oro\Bundle\SearchBundle\Query\Result\Item $item) {
            return $item->getRecordTitle();
        }, $items);

        $this->assertEquals($this->expectedRecordTitles, $recordTitles);
    }
}
