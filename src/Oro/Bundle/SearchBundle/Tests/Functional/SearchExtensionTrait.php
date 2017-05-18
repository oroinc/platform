<?php

namespace Oro\Bundle\SearchBundle\Tests\Functional;

use Oro\Bundle\EntityBundle\ORM\DatabasePlatformInterface;
use Oro\Bundle\EntityBundle\ORM\OroEntityManager;
use Oro\Bundle\SearchBundle\Engine\IndexerInterface;
use Oro\Bundle\SearchBundle\Engine\ObjectMapper;
use Oro\Bundle\SearchBundle\Entity\IndexDatetime;
use Oro\Bundle\SearchBundle\Entity\IndexDecimal;
use Oro\Bundle\SearchBundle\Entity\IndexInteger;
use Oro\Bundle\SearchBundle\Entity\IndexText;
use Oro\Bundle\SearchBundle\Entity\Item;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\SearchBundle\Query\Result;

trait SearchExtensionTrait
{
    /**
     * @return IndexerInterface
     */
    protected function getSearchIndexer()
    {
        return $this->getContainer()->get('oro_search.search.engine.indexer');
    }

    /**
     * @return ObjectMapper
     */
    protected function getSearchObjectMapper()
    {
        return $this->getContainer()->get('oro_search.mapper');
    }

    /**
     * Ensure that items are loaded to search index
     *
     * @param string $alias
     * @param int $itemsCount
     * @param string $searchService
     * @throws \LogicException
     */
    protected function ensureItemsLoaded($alias, $itemsCount, $searchService = 'oro_search.search.engine')
    {
        $query = new Query();
        $query->from($alias);

        $requestCounts = 10;
        do {
            /** @var Result $result */
            $result = $this->getContainer()->get($searchService)->search($query);
            $actualLoaded = $result->getRecordsCount();
            $isLoaded = $actualLoaded === $itemsCount;
            if (!$isLoaded) {
                $requestCounts--;
                sleep(1);
            }
        } while (!$isLoaded && $requestCounts > 0);

        if (!$isLoaded) {
            throw new \LogicException(
                sprintf(
                    'Can\'t ensure the required count of search items are loaded. Expected: %d. Actual: %d',
                    $itemsCount,
                    $actualLoaded
                )
            );
        }
    }

    /**
     * Remove all data added in fixtures
     */
    protected function clearTestData()
    {
        $manager = $this->getContainer()->get('doctrine')->getManager();
        $repository = $manager->getRepository('Oro\Bundle\TestFrameworkBundle\Entity\Item');
        $repository->createQueryBuilder('qb')
            ->delete()
            ->getQuery()
            ->execute();
    }

    /**
     * Workaround to clear MyISAM table as it's not rolled back by transaction.
     * @param string $class
     */
    protected function clearIndexTextTable($class = IndexText::class)
    {
        /** @var OroEntityManager $manager */
        $manager = $this->getContainer()->get('doctrine')->getManager('search');
        if ($manager->getConnection()->getDatabasePlatform()->getName() !== DatabasePlatformInterface::DATABASE_MYSQL) {
            return;
        }

        $repository = $manager->getRepository($class);
        $repository->createQueryBuilder('t')
            ->delete()
            ->getQuery()
            ->execute();
    }
}
