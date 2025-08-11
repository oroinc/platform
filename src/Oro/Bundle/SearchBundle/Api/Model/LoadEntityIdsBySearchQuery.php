<?php

namespace Oro\Bundle\SearchBundle\Api\Model;

use Doctrine\Common\Collections\Expr;
use Oro\Bundle\ApiBundle\Model\LoadEntityIdsQueryInterface;
use Oro\Bundle\SearchBundle\Engine\Indexer as SearchIndexer;
use Oro\Bundle\SearchBundle\Query\Query as SearchQuery;
use Oro\Bundle\SearchBundle\Query\Result as SearchResult;
use Oro\Bundle\SearchBundle\Query\Result\Item as SearchResultItem;

/**
 * A query to load identifiers of entities by the search index.
 */
class LoadEntityIdsBySearchQuery implements LoadEntityIdsQueryInterface
{
    private ?SearchQuery $searchQuery = null;
    private ?SearchResult $searchResult = null;

    public function __construct(
        private readonly SearchIndexer $searchIndexer,
        private readonly SearchQueryExecutorInterface $searchQueryExecutor,
        private readonly string $entityAlias,
        private readonly ?Expr\Expression $searchExpression,
        private readonly ?int $firstResult,
        private readonly ?int $maxResults,
        private readonly array $orderBy,
        private readonly bool $hasMore
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function getEntityIds(): array
    {
        return $this->searchQueryExecutor->execute(function () {
            $entityIds = [];
            /** @var SearchResultItem[] $records */
            $records = $this->getSearchResult()->toArray();
            foreach ($records as $record) {
                $entityIds[] = $record->getRecordId();
            }

            return $entityIds;
        });
    }

    /**
     * {@inheritDoc}
     */
    public function getEntityTotalCount(): int
    {
        return $this->searchQueryExecutor->execute(function () {
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
        return $this->searchQueryExecutor->execute(function () {
            return $this->getSearchResult()->getAggregatedData();
        });
    }

    public function getSearchQuery(): SearchQuery
    {
        if (null === $this->searchQuery) {
            $this->searchQuery = $this->searchIndexer
                ->select()
                ->from([$this->entityAlias]);
        }

        return $this->searchQuery;
    }

    private function getSearchResult(): SearchResult
    {
        if (null === $this->searchResult) {
            $searchQuery = $this->getSearchQuery();
            $criteria = $searchQuery->getCriteria();
            if (null !== $this->searchExpression) {
                $criteria->andWhere($this->searchExpression);
            }
            if ($this->firstResult > 0) {
                $criteria->setFirstResult($this->firstResult);
            }
            $maxResults = $this->getMaxResults();
            if (null !== $maxResults) {
                $criteria->setMaxResults($maxResults);
            }
            if ($this->orderBy) {
                $criteria->orderBy($this->orderBy);
            }
            $this->searchResult = $this->searchIndexer->query($searchQuery);
        }

        return $this->searchResult;
    }

    private function getMaxResults(): ?int
    {
        return (null !== $this->maxResults && $this->hasMore)
            ? $this->maxResults + 1
            : $this->maxResults;
    }
}
