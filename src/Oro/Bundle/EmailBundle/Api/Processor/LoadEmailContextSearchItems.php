<?php

namespace Oro\Bundle\EmailBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\ListContext;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Bundle\ApiBundle\Util\ValueNormalizerUtil;
use Oro\Bundle\EmailBundle\Api\Model\EmailContextSearchItem;
use Oro\Bundle\SearchBundle\Api\SearchEntityListFilterHelper;
use Oro\Bundle\SearchBundle\Engine\Indexer as SearchIndexer;
use Oro\Bundle\SearchBundle\Event\PrepareResultItemEvent;
use Oro\Bundle\SearchBundle\Query\Result\Item as SearchResultItem;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Loads data for the email context search API resource.
 */
class LoadEmailContextSearchItems implements ProcessorInterface
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
            return;
        }

        if (!$entities) {
            $this->setEmptyResult($context);

            return;
        }

        $maxResults = $criteria->getMaxResults();
        $searchQuery = $this->searchIndexer->getSimpleSearchQuery(
            $context->getFilterValues()->getOne('searchText')?->getValue(),
            $criteria->getFirstResult(),
            (null !== $maxResults && $context->getConfig()->getHasMore()) ? $maxResults + 1 : $maxResults,
            array_values($entities)
        );
        $searchResult = $this->searchIndexer->query($searchQuery);
        $context->setResult(
            $this->buildResult($searchResult->toArray(), $maxResults, $context->getRequestType())
        );
        $context->setTotalCountCallback(function () use ($searchResult) {
            return $searchResult->getRecordsCount();
        });
    }

    private function setEmptyResult(ListContext $context): void
    {
        $context->setResult([]);
        $context->setTotalCountCallback(function () {
            return 0;
        });
    }

    private function buildResult(array $records, ?int $maxResults, RequestType $requestType): array
    {
        $hasMore = false;
        if (null !== $maxResults && \count($records) > $maxResults) {
            $records = \array_slice($records, 0, $maxResults);
            $hasMore = true;
        }

        $result = [];
        /** @var SearchResultItem $record */
        foreach ($records as $record) {
            $this->eventDispatcher->dispatch(new PrepareResultItemEvent($record), PrepareResultItemEvent::EVENT_NAME);
            $entityClass = $record->getEntityName();
            $entityId = $record->getRecordId();
            $result[] = new EmailContextSearchItem(
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
