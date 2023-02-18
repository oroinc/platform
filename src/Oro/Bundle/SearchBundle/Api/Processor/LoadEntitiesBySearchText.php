<?php

namespace Oro\Bundle\SearchBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\ListContext;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Bundle\ApiBundle\Util\ValueNormalizerUtil;
use Oro\Bundle\SearchBundle\Api\Model\SearchItem;
use Oro\Bundle\SearchBundle\Api\SearchEntityListFilterHelper;
use Oro\Bundle\SearchBundle\Engine\Indexer as SearchIndexer;
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
    private SearchIndexer $searchIndexer;
    private SearchEntityListFilterHelper $searchEntityListFilterHelper;
    private ValueNormalizer $valueNormalizer;
    private EventDispatcherInterface $eventDispatcher;

    public function __construct(
        SearchIndexer $searchIndexer,
        SearchEntityListFilterHelper $searchEntityListFilterHelper,
        ValueNormalizer $valueNormalizer,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->searchIndexer = $searchIndexer;
        $this->searchEntityListFilterHelper = $searchEntityListFilterHelper;
        $this->valueNormalizer = $valueNormalizer;
        $this->eventDispatcher = $eventDispatcher;
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

        $criteria = $context->getCriteria();
        $limit = $criteria->getMaxResults();
        $searchResult = $this->searchIndexer->simpleSearch(
            $context->getFilterValues()->get('searchText')?->getValue(),
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
            $entityType = ValueNormalizerUtil::tryConvertToEntityType(
                $this->valueNormalizer,
                $entityClass,
                $requestType
            );
            $entityId = $record->getRecordId();
            $result[] = new SearchItem(
                sprintf('%s-%s', $entityType, $entityId),
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
