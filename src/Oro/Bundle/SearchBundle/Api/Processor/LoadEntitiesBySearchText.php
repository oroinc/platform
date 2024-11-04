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

    #[\Override]
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

        $maxResults = $criteria->getMaxResults();
        $searchResult = $this->searchIndexer->simpleSearch(
            $context->getFilterValues()->getOne('searchText')?->getValue(),
            $criteria->getFirstResult(),
            (null !== $maxResults && $context->getConfig()->getHasMore()) ? $maxResults + 1 : $maxResults,
            array_values($entities)
        );

        $context->setResult($this->buildResult($searchResult->toArray(), $maxResults, $context->getRequestType()));
        $context->setTotalCountCallback(function () use ($searchResult) {
            return $searchResult->getRecordsCount();
        });
    }

    /**
     * @param SearchResultItem[] $records
     * @param int|null           $maxResults
     * @param RequestType        $requestType
     *
     * @return SearchItem[]
     */
    private function buildResult(array $records, ?int $maxResults, RequestType $requestType): array
    {
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
                sprintf(
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
