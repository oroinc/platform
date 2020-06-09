<?php

namespace Oro\Bundle\SearchBundle\Tests\Functional\Engine;

use Oro\Bundle\SearchBundle\Engine\Indexer;
use Oro\Bundle\SearchBundle\Query\Criteria\Criteria;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\SearchBundle\Tests\Functional\Controller\DataFixtures\LoadSearchItemData;
use Oro\Bundle\SearchBundle\Tests\Functional\Controller\SearchBundleWebTestCase;
use Oro\Bundle\TestFrameworkBundle\Entity\Item;

/**
 * @group search
 * @dbIsolationPerTest
 */
class EngineStatisticalTest extends SearchBundleWebTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->initClient([], $this->generateBasicAuthHeader());
    }

    public function testGetDocumentsCountGroupByEntityFQCN()
    {
        $this->loadFixture(
            Item::class,
            LoadSearchItemData::class,
            LoadSearchItemData::COUNT
        );

        $alias = $this->getSearchObjectMapper()->getEntityAlias(Item::class);

        $query = new Query();
        $query->from($alias);

        $query
            ->getCriteria()
            ->where(
                Criteria::expr()->contains(
                    Criteria::implodeFieldTypeName(Query::TYPE_TEXT, Indexer::TEXT_ALL_DATA_FIELD),
                    'item'
                )
            );

        $entityFqcnToDocumentCount = $this
            ->getContainer()
            ->get('oro_search.search.engine')
            ->getDocumentsCountGroupByEntityFQCN($query);

        $this->assertEquals([Item::class => LoadSearchItemData::COUNT], $entityFqcnToDocumentCount);
    }
}
