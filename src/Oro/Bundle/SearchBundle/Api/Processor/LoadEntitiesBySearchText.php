<?php

namespace Oro\Bundle\SearchBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\ListContext;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Bundle\ApiBundle\Util\ValueNormalizerUtil;
use Oro\Bundle\SearchBundle\Api\Model\SearchItem;
use Oro\Bundle\SearchBundle\Api\SearchEntityListFilterHelper;
use Oro\Bundle\SearchBundle\Engine\Indexer as SearchIndex;
use Oro\Bundle\SearchBundle\Event\PrepareResultItemEvent;
use Oro\Bundle\SearchBundle\Query\Result\Item as SearchResultItem;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Loads entities for the search API resource.
 */
class LoadEntitiesBySearchText implements ProcessorInterface
{
    private SearchIndex $searchIndex;
    private SearchEntityListFilterHelper $searchEntityListFilterHelper;
    private ValueNormalizer $valueNormalizer;
    private EventDispatcherInterface $eventDispatcher;

    public function __construct(
        SearchIndex $searchIndex,
        SearchEntityListFilterHelper $searchEntityListFilterHelper,
        ValueNormalizer $valueNormalizer,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->searchIndex = $searchIndex;
        $this->searchEntityListFilterHelper = $searchEntityListFilterHelper;
        $this->valueNormalizer = $valueNormalizer;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * {@inheritDoc}
     */
    public function process(ContextInterface $context)
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

        $criteria = $context->getCriteria();
        $limit = $criteria->getMaxResults();
        $searchTextFilterValue = $context->getFilterValues()->get('searchText');
        $searchResult = $this->searchIndex->simpleSearch(
            null !== $searchTextFilterValue ? $searchTextFilterValue->getValue() : null,
            $criteria->getFirstResult(),
            (null !== $limit && $context->getConfig()->getHasMore()) ? $limit + 1 : $limit,
            $entities
        );

        $context->setResult($this->buildSearchResult($searchResult->toArray(), $limit, $context->getRequestType()));
        $context->setTotalCountCallback(function () use ($searchResult) {
            return $searchResult->getRecordsCount();
        });
    }

    /**
     * @param SearchResultItem[] $records
     * @param int|null           $limit
     * @param RequestType        $requestType
     *
     * @return SearchItem[]
     */
    private function buildSearchResult(array $records, ?int $limit, RequestType $requestType): array
    {
        $hasMore = false;
        if (null !== $limit && \count($records) > $limit) {
            $records = \array_slice($records, 0, $limit);
            $hasMore = true;
        }

        $result = [];
        foreach ($records as $record) {
            $this->eventDispatcher->dispatch(new PrepareResultItemEvent($record), PrepareResultItemEvent::EVENT_NAME);
            $entityClass = $record->getEntityName();
            $entityType = ValueNormalizerUtil::convertToEntityType(
                $this->valueNormalizer,
                $entityClass,
                $requestType,
                false
            );
            $entityId = $record->getRecordId();
            $result[] = new SearchItem(
                sprintf('%s-%s', $entityType, $entityId),
                $entityClass,
                $entityId,
                $record->getRecordTitle(),
                $record->getRecordUrl()
            );
        }

        if ($hasMore) {
            $result[ConfigUtil::INFO_RECORD_KEY] = [ConfigUtil::HAS_MORE => true];
        }

        return $result;
    }
}
