<?php

namespace Oro\Bundle\SearchBundle\Api\Processor;

use Doctrine\Common\Collections\Criteria;
use Oro\Bundle\ApiBundle\Exception\InvalidFilterException;
use Oro\Bundle\ApiBundle\Exception\RuntimeException;
use Oro\Bundle\ApiBundle\Filter\FilterValue;
use Oro\Bundle\ApiBundle\Filter\FilterValueAccessorInterface;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Model\ErrorSource;
use Oro\Bundle\ApiBundle\Processor\ListContext;
use Oro\Bundle\ApiBundle\Request\Constraint;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Bundle\ApiBundle\Util\ValueNormalizerUtil;
use Oro\Bundle\SearchBundle\Api\Exception\InvalidSearchQueryException;
use Oro\Bundle\SearchBundle\Api\Model\SearchItem;
use Oro\Bundle\SearchBundle\Api\Model\SearchQueryExecutorInterface;
use Oro\Bundle\SearchBundle\Api\Processor\MultiTargetSearch\MultiTargetAggregatedDataJoiner;
use Oro\Bundle\SearchBundle\Api\Processor\MultiTargetSearch\MultiTargetSearchAggregationParser;
use Oro\Bundle\SearchBundle\Api\Processor\MultiTargetSearch\MultiTargetSearchExpressionLexer;
use Oro\Bundle\SearchBundle\Api\Processor\MultiTargetSearch\MultiTargetSearchMappingProvider;
use Oro\Bundle\SearchBundle\Api\SearchEntityListFilterHelper;
use Oro\Bundle\SearchBundle\Api\SearchMappingProvider;
use Oro\Bundle\SearchBundle\Engine\Indexer as SearchIndexer;
use Oro\Bundle\SearchBundle\Event\PrepareResultItemEvent;
use Oro\Bundle\SearchBundle\Exception\ExpressionSyntaxError;
use Oro\Bundle\SearchBundle\Query\Expression\Parser as SearchQueryParser;
use Oro\Bundle\SearchBundle\Query\Query as SearchQuery;
use Oro\Bundle\SearchBundle\Query\Result as SearchResult;
use Oro\Bundle\SearchBundle\Query\Result\Item as SearchResultItem;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Loads entities for the search API resource.
 */
class LoadEntitiesBySearchText implements ProcessorInterface
{
    private SearchIndexer $searchIndexer;
    private SearchQueryExecutorInterface $searchQueryExecutor;
    private SearchEntityListFilterHelper $searchEntityListFilterHelper;
    private SearchMappingProvider $searchMappingProvider;
    private ValueNormalizer $valueNormalizer;
    private EventDispatcherInterface $eventDispatcher;

    public function __construct(
        SearchIndexer $searchIndexer,
        SearchQueryExecutorInterface $searchQueryExecutor,
        SearchEntityListFilterHelper $searchEntityListFilterHelper,
        SearchMappingProvider $searchMappingProvider,
        ValueNormalizer $valueNormalizer,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->searchIndexer = $searchIndexer;
        $this->searchEntityListFilterHelper = $searchEntityListFilterHelper;
        $this->valueNormalizer = $valueNormalizer;
        $this->eventDispatcher = $eventDispatcher;
        $this->searchQueryExecutor = $searchQueryExecutor;
        $this->searchMappingProvider = $searchMappingProvider;
    }

    /**
     * {@inheritDoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var ListContext $context */

        if ($context->hasResult()) {
            // result data are already retrieved
            return;
        }

        $criteria = $context->getCriteria();
        if (null === $criteria) {
            // something going wrong, it is expected that the criteria exists
            return;
        }

        $entities = $this->searchEntityListFilterHelper->getEntities($context, 'entities');
        if ($context->hasErrors()) {
            // the "entities" filter has some invalid data
            return;
        }

        if (!$entities) {
            // the "entities" filter does not have any entities allowed for the current logged in user
            $context->setResult([]);
            $context->setTotalCountCallback(function () {
                return 0;
            });

            return;
        }

        $this->processSearchQuery($criteria, $entities, $context);
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    private function processSearchQuery(Criteria $criteria, array $entities, ListContext $context): void
    {
        $entityClasses = array_keys($entities);
        $entityAliases = array_values($entities);

        $searchMappingProvider = new MultiTargetSearchMappingProvider($this->searchMappingProvider);
        $fieldMappings = $searchMappingProvider->getFieldMappings($entityClasses);
        $SearchFieldMappings = $searchMappingProvider->getSearchFieldMappings($entityClasses, $fieldMappings);

        $filterValues = $context->getFilterValues();
        try {
            $query = $this->buildSearchQuery($entityAliases, $filterValues, $SearchFieldMappings, $fieldMappings);
        } catch (\Exception $e) {
            $context->addError($this->createSearchQueryBuildingError($e, $filterValues));

            return;
        }

        $toJoinAggregation = [];
        $aggregationFilterValue = $filterValues->get('aggregations');
        if (null !== $aggregationFilterValue && $aggregationFilterValue->getValue()) {
            $searchAggregationParser = new MultiTargetSearchAggregationParser($SearchFieldMappings, $fieldMappings);
            try {
                $aggregations = $searchAggregationParser->parse(
                    (array)$aggregationFilterValue->getValue()
                );
            } catch (InvalidFilterException $e) {
                $context->addError($this->createAggregatedDataError($e, $aggregationFilterValue));

                return;
            }
            $aggregationDataTypes = [];
            foreach ($aggregations as $alias => $fieldAggregations) {
                $aggregationDataType = null;
                $hasSeveralFieldAggregations = \count($fieldAggregations) > 1;
                foreach ($fieldAggregations as $i => [$fieldName, $fieldType, $function]) {
                    $fieldAlias = $alias;
                    if ($hasSeveralFieldAggregations) {
                        $fieldAlias = $alias . '__to_join_part_' . $i;
                        $toJoinAggregation[$alias][$function][$fieldType][] = $fieldAlias;
                    }
                    $query->addAggregate($fieldAlias, $fieldType . '.' . $fieldName, $function);
                    if (null === $aggregationDataType) {
                        $aggregationDataType = $fieldType;
                    }
                }
                $aggregationDataTypes[$alias] = $aggregationDataType;
            }
            $context->set(NormalizeSearchAggregatedData::AGGREGATION_DATA_TYPES, $aggregationDataTypes);
        }

        $maxResults = $criteria->getMaxResults();
        $searchCriteria = $query->getCriteria();
        $searchCriteria->setFirstResult($criteria->getFirstResult());
        $searchCriteria->setMaxResults($this->getMaxResults($maxResults, (bool)$context->getConfig()?->getHasMore()));

        try {
            /** @var SearchResult $searchResult */
            $searchResult = $this->searchQueryExecutor->execute(fn () => $this->searchIndexer->query($query));
            $resultData = $this->buildResult($searchResult, $maxResults, $context->getRequestType());
        } catch (InvalidSearchQueryException $e) {
            $context->addError($this->createSearchQueryBuildingError($e, $filterValues));

            return;
        }
        $context->setResult($resultData);
        $context->setTotalCountCallback(function () use ($searchResult) {
            return $this->searchQueryExecutor->execute(fn () => $searchResult->getRecordsCount());
        });

        try {
            /** @var array $aggregatedData */
            $aggregatedData = $this->searchQueryExecutor->execute(fn () => $searchResult->getAggregatedData());
        } catch (InvalidSearchQueryException $e) {
            $context->addError($this->createAggregatedDataError($e, $aggregationFilterValue));

            return;
        }
        if ($aggregatedData) {
            $aggregatedDataJoiner = new MultiTargetAggregatedDataJoiner();
            $context->addInfoRecord(
                'aggregatedData',
                $aggregatedDataJoiner->join($aggregatedData, $toJoinAggregation)
            );
        }
    }

    private function buildSearchQuery(
        array $entityAliases,
        FilterValueAccessorInterface $filterValues,
        array $searchFieldMappings,
        array $fieldMappings
    ): SearchQuery {
        $searchExpression = $this->getSearchExpression($filterValues);

        $parser = new SearchQueryParser();
        $searchExpressionLexer = new MultiTargetSearchExpressionLexer($searchFieldMappings, $fieldMappings);

        $query = $this->searchIndexer->select();
        try {
            $parser->parse(
                $searchExpressionLexer->tokenize($searchExpression),
                $query,
                null,
                SearchQuery::KEYWORD_WHERE
            );
        } catch (ExpressionSyntaxError $e) {
            throw new InvalidFilterException($e->getMessage());
        }
        $query->from($entityAliases);
        $query->addSelect('text.system_entity_name as name');

        return $query;
    }

    private function getSearchExpression(FilterValueAccessorInterface $filterValues): string
    {
        $searchQuery = $filterValues->get('searchQuery')?->getValue() ?? '';
        $searchText = $filterValues->get('searchText')?->getValue();
        if ($searchText) {
            if ($searchQuery) {
                $searchQuery = \sprintf('(%s) and ', $searchQuery);
            }
            $searchQuery .= \sprintf('%s ~ "%s"', $this->searchMappingProvider->getAllTextFieldName(), $searchText);
        }

        return $searchQuery;
    }

    private function getMaxResults(?int $maxResults, bool $hasMore): ?int
    {
        return (null !== $maxResults && $hasMore)
            ? $maxResults + 1
            : $maxResults;
    }

    private function createSearchQueryBuildingError(\Exception $e, FilterValueAccessorInterface $filterValues): Error
    {
        $searchQueryFilterValue = $filterValues->get('searchQuery');

        return null === $searchQueryFilterValue || !$searchQueryFilterValue->getSourceKey()
            ? Error::createByException($e)
            : Error::createValidationError(Constraint::FILTER)
                ->setInnerException($e)
                ->setSource(ErrorSource::createByParameter($searchQueryFilterValue->getSourceKey()));
    }

    private function createAggregatedDataError(
        RuntimeException $e,
        ?FilterValue $aggregationFilterValue
    ): Error {
        $error = Error::createValidationError(Constraint::FILTER, $e->getMessage());
        if (null !== $aggregationFilterValue) {
            $error->setSource(ErrorSource::createByParameter($aggregationFilterValue->getSourceKey()));
        }

        return $error;
    }

    /**
     * @return SearchItem[]
     */
    private function buildResult(SearchResult $searchResult, ?int $maxResults, RequestType $requestType): array
    {
        /** @var SearchResultItem[] $records */
        $records = $this->searchQueryExecutor->execute(fn () => $searchResult->toArray());

        $hasMore = false;
        if (null !== $maxResults && \count($records) > $maxResults) {
            $records = \array_slice($records, 0, $maxResults);
            $hasMore = true;
        }

        $result = [];
        foreach ($records as $record) {
            $this->eventDispatcher->dispatch(new PrepareResultItemEvent($record), PrepareResultItemEvent::EVENT_NAME);
            $entityClass = $record->getEntityName();
            $entityId = $record->getRecordId();
            $result[] = new SearchItem(
                \sprintf(
                    '%s-%s',
                    ValueNormalizerUtil::convertToEntityType($this->valueNormalizer, $entityClass, $requestType),
                    $entityId
                ),
                $entityClass,
                $entityId,
                $record->getSelectedData()['name'],
                $record->getRecordUrl()
            );
        }

        if ($hasMore) {
            $result[ConfigUtil::INFO_RECORD_KEY] = [ConfigUtil::HAS_MORE => true];
        }

        return $result;
    }
}
