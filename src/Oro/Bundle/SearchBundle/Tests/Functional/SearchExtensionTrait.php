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
    protected static function getSearchIndexer(): IndexerInterface
    {
        return self::getContainer()->get('oro_search.search.engine.indexer');
    }

    /**
     * @return ObjectMapper
     */
    protected static function getSearchObjectMapper()
    {
        return self::getContainer()->get('oro_search.mapper');
    }

    protected static function getIndexAgent()
    {
        return self::getContainer()->get('oro_elasticsearch.engine.index_agent');
    }

    protected static function getIndexPrefix()
    {
        return static::getContainer()->get('oro_search.engine.parameters')->getParamValue('prefix');
    }

    /**
     * Ensure that items are loaded to search index.
     */
    protected static function ensureItemsLoaded(
        string $classOrAlias,
        int $itemsCount,
        string $searchService = 'oro_search.search.engine'
    ): void {
        if (class_exists($classOrAlias)) {
            $alias = self::getIndexAlias($classOrAlias, []);
        } else {
            $alias = $classOrAlias;
        }

        $query = new Query();
        $query->from($alias);

        $requestCounts = 30;
        do {
            /** @var Result $result */
            $result = self::getContainer()->get($searchService)->search($query);
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

    protected static function getIndexAlias(string $className, array $placeholders): string
    {
        return self::getContainer()
            ->get('oro_search.provider.search_mapping')
            ->getEntityAlias($className);
    }

    /**
     * Remove all data added in fixtures
     */
    protected static function clearTestData(string $entity = Item::class)
    {
        $manager = self::getContainer()->get('doctrine')->getManager();
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

    protected static function resetIndex(array|string|null $class = null, array $context = []): void
    {
        self::getSearchIndexer()->resetIndex($class, $context);
    }

    /**
     * @param string[]|string|null $class
     * @param array $context
     */
    protected static function reindex(array|string|null $class = null, array $context = []): void
    {
        self::getSearchIndexer()->reindex($class, $context);
    }

    protected static function deleteIndices($indexAgent = null)
    {
        if (!$indexAgent) {
            $indexAgent = static::getIndexAgent();
        }

        $indexPrefix = static::getIndexPrefix();
        $indices = $indexAgent->getClient()->indices()->get(['index' => $indexPrefix . '*'])->asArray();
        foreach (array_chunk($indices, 10, true) as $chunk) {
            $indexAgent->getClient()->indices()->delete(['index' => implode(',', array_keys($chunk))]);
        }
    }
}
