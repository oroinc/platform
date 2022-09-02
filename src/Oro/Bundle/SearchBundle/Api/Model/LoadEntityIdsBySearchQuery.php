<?php

namespace Oro\Bundle\SearchBundle\Api\Model;

use Oro\Bundle\ApiBundle\Exception\InvalidSorterException;
use Oro\Bundle\ApiBundle\Model\LoadEntityIdsQueryInterface;
use Oro\Bundle\SearchBundle\Engine\Indexer as SearchIndexer;
use Oro\Bundle\SearchBundle\Provider\AbstractSearchMappingProvider;
use Oro\Bundle\SearchBundle\Query\Criteria\Criteria as SearchCriteria;
use Oro\Bundle\SearchBundle\Query\Query as SearchQuery;
use Oro\Bundle\SearchBundle\Query\Result as SearchResult;
use Oro\Bundle\SearchBundle\Query\Result\Item as SearchResultItem;

/**
 * A query to load identifiers of entities by a search index.
 */
class LoadEntityIdsBySearchQuery implements LoadEntityIdsQueryInterface
{
    private SearchIndexer $searchIndexer;
    private AbstractSearchMappingProvider $searchMappingProvider;
    private string $entityClass;
    private string $searchText;
    private ?int $firstResult;
    private ?int $maxResults;
    private array $orderBy;
    private bool $hasMore;
    private ?SearchResult $searchResult = null;

    public function __construct(
        SearchIndexer $searchIndexer,
        AbstractSearchMappingProvider $searchMappingProvider,
        string $entityClass,
        string $searchText,
        ?int $firstResult,
        ?int $maxResults,
        array $orderBy,
        bool $hasMore
    ) {
        $this->searchIndexer = $searchIndexer;
        $this->searchMappingProvider = $searchMappingProvider;
        $this->entityClass = $entityClass;
        $this->searchText = $searchText;
        $this->firstResult = $firstResult;
        $this->maxResults = $maxResults;
        $this->orderBy = $orderBy;
        $this->hasMore = $hasMore;
    }

    /**
     * {@inheritDoc}
     */
    public function getEntityIds(): array
    {
        $entityIds = [];
        /** @var SearchResultItem[] $records */
        $records = $this->getSearchResult()->toArray();
        foreach ($records as $record) {
            $entityIds[] = $record->getRecordId();
        }

        return $entityIds;
    }

    /**
     * {@inheritDoc}
     */
    public function getEntityTotalCount(): int
    {
        return $this->getSearchResult()->getRecordsCount();
    }

    private function getSearchResult(): SearchResult
    {
        if (null === $this->searchResult) {
            $query = $this->searchIndexer
                ->select()
                ->from([$this->searchMappingProvider->getEntityAlias($this->entityClass)]);
            $criteria = $query->getCriteria();
            $criteria->where(SearchCriteria::expr()->contains(
                SearchCriteria::implodeFieldTypeName(SearchQuery::TYPE_TEXT, SearchIndexer::TEXT_ALL_DATA_FIELD),
                $this->searchText
            ));
            if ($this->firstResult > 0) {
                $criteria->setFirstResult($this->firstResult);
            }
            $maxResults = $this->getMaxResults();
            if (null !== $maxResults) {
                $criteria->setMaxResults($maxResults);
            }
            if ($this->orderBy) {
                $criteria->orderBy($this->getOrderBy());
            }

            $this->searchResult = $this->searchIndexer->query($query);
        }

        return $this->searchResult;
    }

    private function getMaxResults(): ?int
    {
        return (null !== $this->maxResults && $this->hasMore) ? $this->maxResults + 1 : $this->maxResults;
    }

    private function getOrderBy(): array
    {
        $result = [];
        $mapping = $this->searchMappingProvider->getEntityConfig($this->entityClass);
        foreach ($this->orderBy as $fieldName => $direction) {
            $searchFieldName = $this->findSearchFieldName($fieldName, $mapping);
            if (null === $searchFieldName) {
                throw new InvalidSorterException(sprintf('Sorting by "%s" field is not supported.', $fieldName));
            }
            $result[$searchFieldName] = $direction;
        }

        return $result;
    }

    private function findSearchFieldName(string $fieldName, array $mapping): ?string
    {
        if (empty($mapping['fields'])) {
            return null;
        }

        foreach ($mapping['fields'] as $field) {
            if (empty($field['target_fields'])) {
                continue;
            }
            $targetFields = $field['target_fields'];
            if (count($targetFields) !== 1) {
                continue;
            }
            if ($field['name'] === $fieldName) {
                return SearchCriteria::implodeFieldTypeName($field['target_type'], reset($targetFields));
            }
        }

        return null;
    }
}
