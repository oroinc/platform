<?php

namespace Oro\Bundle\SearchBundle\Tests\Functional;

use Oro\Bundle\SearchBundle\Engine\IndexerInterface;
use Oro\Bundle\SearchBundle\Engine\ObjectMapper;
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

        $requestCounts = 5;
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
}
