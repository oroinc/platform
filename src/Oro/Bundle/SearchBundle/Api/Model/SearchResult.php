<?php

namespace Oro\Bundle\SearchBundle\Api\Model;

use Oro\Bundle\SearchBundle\Query\Result;
use Oro\Bundle\SearchBundle\Query\SearchQueryInterface;
use Oro\Component\EntitySerializer\ConfigUtil;

/**
 * Represents a search query result.
 */
class SearchResult
{
    private ?int $limit = null;
    private ?Result $searchResult = null;

    /**
     * @param SearchQueryInterface         $query
     * @param SearchQueryExecutorInterface $queryExecutor
     * @param bool                         $hasMore       Indicates whether an additional record with
     *                                                    key "_" {@see ConfigUtil::INFO_RECORD_KEY}
     *                                                    and value ['has_more' => true] {@see ConfigUtil::HAS_MORE}
     *                                                    should be added to the collection of records
     *                                                    if the search index has more records than it was requested.
     */
    public function __construct(
        private readonly SearchQueryInterface $query,
        private readonly SearchQueryExecutorInterface $queryExecutor,
        private readonly bool $hasMore = false
    ) {
    }

    /**
     * Gets search query result records.
     *
     * @return Result\Item[]
     */
    public function getRecords(): array
    {
        return $this->queryExecutor->execute(function () {
            $records = $this->getSearchResult()->getElements();
            if (null !== $this->limit && \count($records) > $this->limit) {
                $records = \array_slice($records, 0, $this->limit);
                $records[ConfigUtil::INFO_RECORD_KEY] = [ConfigUtil::HAS_MORE => true];
            }

            return $records;
        });
    }

    /**
     * Gets the number of search query result records without limit parameters.
     */
    public function getRecordsCount(): int
    {
        return $this->queryExecutor->execute(function () {
            return $this->getSearchResult()->getRecordsCount();
        });
    }

    /**
     * Gets aggregated data collected when execution the query.
     * Format for the "count" function: [aggregating name => [field value => count value, ...], ...]
     * Format for mathematical functions: [aggregating name => aggregated value, ...]
     */
    public function getAggregatedData(): array
    {
        return $this->queryExecutor->execute(function () {
            return $this->getSearchResult()->getAggregatedData();
        });
    }

    private function getSearchResult(): Result
    {
        if (null === $this->searchResult) {
            $query = $this->query;
            if ($this->hasMore) {
                $this->limit = $query->getMaxResults();
                if (null !== $this->limit) {
                    $query = clone $query;
                    $query->setMaxResults($this->limit + 1);
                }
            }
            $this->searchResult = $query->getResult();
        }

        return $this->searchResult;
    }
}
