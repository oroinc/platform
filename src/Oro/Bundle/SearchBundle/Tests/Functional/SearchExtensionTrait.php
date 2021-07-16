<?php

namespace Oro\Bundle\SearchBundle\Tests\Functional;

use Oro\Bundle\EntityBundle\ORM\DatabasePlatformInterface;
use Oro\Bundle\EntityBundle\ORM\OroEntityManager;
use Oro\Bundle\SearchBundle\Engine\IndexerInterface;
use Oro\Bundle\SearchBundle\Engine\ObjectMapper;
use Oro\Bundle\SearchBundle\Entity\IndexText;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\SearchBundle\Query\Result;
use Oro\Bundle\TestFrameworkBundle\Entity\Item;

trait SearchExtensionTrait
{
    /**
     * @return IndexerInterface
     */
    protected static function getSearchIndexer()
    {
        return static::getContainer()->get('oro_search.search.engine.indexer');
    }

    /**
     * @return ObjectMapper
     */
    protected static function getSearchObjectMapper()
    {
        return static::getContainer()->get('oro_search.mapper');
    }

    /**
     * Ensure that items are loaded to search index
     *
     * @param string $alias
     * @param int $itemsCount
     * @param string $searchService
     * @throws \LogicException
     */
    protected static function ensureItemsLoaded($alias, $itemsCount, $searchService = 'oro_search.search.engine')
    {
        $query = new Query();
        $query->from($alias);

        $requestCounts = 30;
        do {
            /** @var Result $result */
            $result = static::getContainer()->get($searchService)->search($query);
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
    protected static function clearTestData(string $entity = Item::class)
    {
        $manager = static::getContainer()->get('doctrine')->getManager();
        $repository = $manager->getRepository($entity);
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
