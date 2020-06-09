<?php

namespace Oro\Bundle\SearchBundle\Tests\Functional\EventListener;

use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\SearchBundle\Tests\Functional\Controller\SearchBundleWebTestCase;
use Oro\Bundle\SearchBundle\Tests\Functional\EventListener\DataFixtures\LoadSearchItemData;
use Oro\Bundle\TestFrameworkBundle\Entity\Item;

class LimitResultsListenerTest extends SearchBundleWebTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->initClient();

        $this->loadFixture(Item::class, LoadSearchItemData::class, LoadSearchItemData::COUNT);
    }

    public function testSearchWithLimit(): void
    {
        $alias = $this->getSearchObjectMapper()->getEntityAlias(Item::class);

        $query = new Query();
        $query->from($alias);
        $query->getCriteria()->setMaxResults(1001);

        $result = self::getContainer()->get('oro_search.search.engine')->search($query);

        self::assertEquals(1000, $result->count());
    }
}
