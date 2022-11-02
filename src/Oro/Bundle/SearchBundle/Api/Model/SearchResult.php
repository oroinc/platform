<?php

namespace Oro\Bundle\SearchBundle\Api\Model;

use Doctrine\DBAL\Exception\DriverException;
use Oro\Bundle\SearchBundle\Api\Exception\InvalidSearchQueryException;
use Oro\Bundle\SearchBundle\Query\Result;
use Oro\Bundle\SearchBundle\Query\SearchQueryInterface;
use Oro\Component\EntitySerializer\ConfigUtil;

/**
 * Represents a search query result.
 */
class SearchResult
{
    private SearchQueryInterface $query;
    private bool $hasMore;
    private ?int $limit = null;
    private ?Result $searchResult = null;

    /**
     * @param SearchQueryInterface $query
     * @param bool                 $hasMore Indicates whether an additional record with
     *                                      key "_" {@see \Oro\Component\EntitySerializer\ConfigUtil::INFO_RECORD_KEY}
     *                                      and value ['has_more' => true]
     *                                      {@see \Oro\Component\EntitySerializer\ConfigUtil::HAS_MORE}
     *                                      should be added to the collection of records
     *                                      if the search index has more records than it was requested.
     */
    public function __construct(SearchQueryInterface $query, bool $hasMore = false)
    {
        $this->query = $query;
        $this->hasMore = $hasMore;
    }

    /**
     * Gets search query result records.
     *
     * @return Result\Item[]
     */
    public function getRecords(): array
    {
        return $this->execute(function () {
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
        return $this->execute(function () {
            return $this->getSearchResult()->getRecordsCount();
        });
    }

    /**
     * Gets aggregated data collected when execution the query.
     * Format for the "count" function: [aggregating name => ['value' => field value, 'count' => count value], ...]
     * Format for mathematical functions: [aggregating name => aggregated value, ...]
     */
    public function getAggregatedData(): array
    {
        return $this->execute(function () {
            return $this->normalizeAggregatedData(
                $this->getSearchResult()->getAggregatedData()
            );
        });
    }

    private function getSearchResult(): Result
    {
        if (null === $this->searchResult) {
            if ($this->hasMore) {
                $this->limit = $this->query->getMaxResults();
                if (null !== $this->limit) {
                    $this->query = clone $this->query;
                    $this->query->setMaxResults($this->limit + 1);
                }
            }
            $this->searchResult = $this->query->getResult();
        }

        return $this->searchResult;
    }

    private function execute(callable $callback): mixed
    {
        try {
            return $callback();
        } catch (\Exception $e) {
            if ($e instanceof DriverException
                || (
                    class_exists('Elastic\Elasticsearch\Exception\ClientResponseException')
                    && $e instanceof \Elastic\Elasticsearch\Exception\ClientResponseException
                )
            ) {
                throw new InvalidSearchQueryException('Invalid search query.', $e->getCode(), $e);
            }

            throw $e;
        }
    }

    private function normalizeAggregatedData(array $aggregatedData): array
    {
        $result = [];
        foreach ($aggregatedData as $name => $value) {
            if (\is_array($value)) {
                // "count" aggregation
                $resultValue = [];
                foreach ($value as $key => $val) {
                    $resultValue[] = ['value' => $key, 'count' => $val];
                }
                $value = $resultValue;
            }
            $result[$name] = $value;
        }

        return $result;
    }
}
