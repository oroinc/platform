<?php

namespace Oro\Bundle\EmailBundle\Api\Processor;

use Doctrine\Common\Collections\Criteria;
use Oro\Bundle\ActivityBundle\Manager\ActivityManager;
use Oro\Bundle\ApiBundle\Filter\FilterValue;
use Oro\Bundle\ApiBundle\Filter\FilterValueAccessorInterface;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Model\ErrorSource;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Processor\ListContext;
use Oro\Bundle\ApiBundle\Request\Constraint;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\ApiBundle\Util\ValueNormalizerUtil;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Tools\EmailAddressHelper;
use Oro\Bundle\SearchBundle\Api\SearchEntityListFilterHelper;
use Oro\Bundle\SearchBundle\Engine\Indexer as SearchIndexer;
use Oro\Bundle\SearchBundle\Event\PrepareResultItemEvent;
use Oro\Bundle\SearchBundle\Query\Result as SearchResult;
use Oro\Bundle\SearchBundle\Query\Result\Item as SearchResultItem;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * The base class for processors that load data for the email context API resources.
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
abstract class AbstractLoadEmailContextItems implements ProcessorInterface
{
    protected DoctrineHelper $doctrineHelper;
    protected SearchIndexer $searchIndexer;
    protected SearchEntityListFilterHelper $searchEntityListFilterHelper;
    protected ActivityManager $activityManager;
    protected ValueNormalizer $valueNormalizer;
    protected EventDispatcherInterface $eventDispatcher;
    protected EmailAddressHelper $emailAddressHelper;
    protected TokenAccessorInterface $tokenAccessor;

    public function __construct(
        DoctrineHelper $doctrineHelper,
        SearchIndexer $searchIndexer,
        SearchEntityListFilterHelper $searchEntityListFilterHelper,
        ActivityManager $activityManager,
        ValueNormalizer $valueNormalizer,
        EventDispatcherInterface $eventDispatcher,
        EmailAddressHelper $emailAddressHelper,
        TokenAccessorInterface $tokenAccessor
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->searchIndexer = $searchIndexer;
        $this->searchEntityListFilterHelper = $searchEntityListFilterHelper;
        $this->activityManager = $activityManager;
        $this->valueNormalizer = $valueNormalizer;
        $this->eventDispatcher = $eventDispatcher;
        $this->emailAddressHelper = $emailAddressHelper;
        $this->tokenAccessor = $tokenAccessor;
    }

    abstract protected function createResultItem(
        string $id,
        string $entityClass,
        mixed $entityId,
        ?string $entityName,
        ?string $entityUrl,
        bool $isContext
    ): object;

    protected function getRequestedEntities(Context $context): array
    {
        return $this->searchEntityListFilterHelper->getEntities($context, 'entities');
    }

    protected function getRequestedExcludeCurrentUser(Context $context): ?bool
    {
        return $context->getFilterValues()->get('excludeCurrentUser')?->getValue();
    }

    protected function getRequestedIsContext(Context $context): ?bool
    {
        return $context->getFilterValues()->get('isContext')?->getValue();
    }

    protected function getRequestedMessageIds(Context $context): ?array
    {
        $value = $this->getRequestedMessageId($context);
        if (null !== $value && !\is_array($value)) {
            $value = [$value];
        }

        return $value;
    }

    protected function getRequestedMessageId(Context $context): mixed
    {
        $filterValue = $context->getFilterValues()->get('messageId');
        $value = $filterValue?->getValue();
        if (!$value) {
            $context->addError(
                Error::createValidationError(Constraint::FILTER, 'The Message-ID is required.')
                    ->setSource(ErrorSource::createByParameter($this->getMessageIdFilterKey($filterValue, $context)))
            );
            $value = null;
        }

        return $value;
    }

    protected function getMessageIdFilterKey(?FilterValue $filterValue, Context $context): string
    {
        $filterKey = $filterValue?->getSourceKey();
        if (!$filterKey) {
            $filterGroup = $context->getFilters()->getDefaultGroupName();
            $filterKey = $filterGroup
                ? $context->getFilters()->getGroupedFilterKey($filterGroup, 'messageId')
                : 'messageId';
        }

        return $filterKey;
    }

    protected function getRequestedEmailAddresses(FilterValueAccessorInterface $filterValues): array
    {
        $emailAddresses = [];
        $emailAddress = $this->emailAddressHelper->extractPureEmailAddress($filterValues->get('from')?->getValue());
        if ($emailAddress) {
            $emailAddresses[] = $emailAddress;
        }
        $values = $filterValues->get('to')?->getValue();
        if ($values) {
            foreach ((array)$values as $val) {
                $emailAddress = $this->emailAddressHelper->extractPureEmailAddress($val);
                if ($emailAddress) {
                    $emailAddresses[] = $emailAddress;
                }
            }
        }
        $values = $filterValues->get('cc')?->getValue();
        if ($values) {
            foreach ((array)$values as $val) {
                $emailAddress = $this->emailAddressHelper->extractPureEmailAddress($val);
                if ($emailAddress) {
                    $emailAddresses[] = $emailAddress;
                }
            }
        }

        return array_values(array_unique($emailAddresses));
    }

    protected function getRequestedSearchText(
        Context $context,
        array $emailAddresses,
        ?bool $excludeCurrentUser,
        ?bool $isContext
    ): ?string {
        $filterValue = $context->getFilterValues()->get('searchText');
        if (null === $filterValue) {
            return null;
        }

        $searchText = $filterValue->getValue();
        if ($searchText && ($emailAddresses || null !== $excludeCurrentUser || null !== $isContext)) {
            $context->addError(
                Error::createValidationError(Constraint::FILTER, 'The search text cannot be specified together with'
                    . ' "from", "to", "cc", "isContext" or "excludeCurrentUser" filters.')
                    ->setSource(ErrorSource::createByParameter($filterValue->getSourceKey()))
            );
        }

        return $searchText;
    }

    protected function setEmptyResult(ListContext $context): void
    {
        $context->setResult([]);
        $context->setTotalCountCallback(function () {
            return 0;
        });
    }

    protected function loadAndSetResult(
        ListContext $context,
        Criteria $criteria,
        array $entities,
        array $emailIds,
        array $existingEmailAddresses,
        array $requestedEmailAddresses,
        bool $excludeCurrentUser,
        ?bool $isContext
    ): void {
        $emailAddresses = $existingEmailAddresses
            ? array_values(array_unique(array_merge($requestedEmailAddresses, $existingEmailAddresses)))
            : $requestedEmailAddresses;

        $records = $this->loadData($entities, $emailAddresses, $excludeCurrentUser, $isContext, $emailIds);

        $totalCount = \count($records);

        $maxResults = $criteria->getMaxResults();
        if (null !== $maxResults) {
            $records = \array_slice(
                $records,
                $criteria->getFirstResult() ?? 0,
                $context->getConfig()->getHasMore() ? $maxResults + 1 : $maxResults
            );
        }

        $context->setResult($this->buildResult($records, $maxResults, $context->getRequestType()));
        $context->setTotalCountCallback(function () use ($totalCount) {
            return $totalCount;
        });
    }

    protected function loadAndSetResultBySearchText(
        ListContext $context,
        Criteria $criteria,
        array $entities,
        array $emailIds,
        string $searchText
    ): void {
        $maxResults = $criteria->getMaxResults();

        $searchQuery = $this->searchIndexer->getSimpleSearchQuery(
            $searchText,
            $criteria->getFirstResult(),
            (null !== $maxResults && $context->getConfig()->getHasMore()) ? $maxResults + 1 : $maxResults,
            array_values($entities)
        );

        $searchResult = $this->searchIndexer->query($searchQuery);

        $records = $this->convertSearchResultToArray($searchResult);

        if ($emailIds) {
            $assignedEntities = $this->loadAssignedEntities($emailIds, array_keys($entities));
            if ($assignedEntities) {
                $records = $this->markAssignedEntities($records, $assignedEntities);
            }
        }

        $context->setResult($this->buildResult($records, $maxResults, $context->getRequestType()));
        $context->setTotalCountCallback(function () use ($searchResult) {
            return $searchResult->getRecordsCount();
        });
    }

    /**
     * @param array $rows [['id' => email id, 'f' => FROM email address, 't' => TO email address], ...]
     *
     * @return array [[email id, ...], [email address, ...]]
     */
    protected function buildEmailIdsAndItsAddresses(array $rows): array
    {
        if (!$rows) {
            return [[], []];
        }

        $emailIds = [];
        $emailAddresses = [];
        foreach ($rows as $row) {
            $emailId = $row['id'];
            if (!isset($emailIds[$emailId])) {
                $emailIds[$emailId] = true;
            }
            $emailAddress = $row['f'];
            if ($emailAddress && !isset($emailAddresses[$emailAddress])) {
                $emailAddresses[$emailAddress] = true;
            }
            $emailAddress = $row['t'];
            if ($emailAddress && !isset($emailAddresses[$emailAddress])) {
                $emailAddresses[$emailAddress] = true;
            }
        }

        return [array_keys($emailIds), array_keys($emailAddresses)];
    }

    protected function buildResultItemId(array $record, RequestType $requestType): string
    {
        return sprintf(
            '%s-%s',
            ValueNormalizerUtil::convertToEntityType($this->valueNormalizer, $record['entity'], $requestType),
            $record['id']
        );
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function loadData(
        array $entities,
        array $emailAddresses,
        bool $excludeCurrentUser,
        ?bool $isContext,
        array $emailIds
    ): array {
        $records = true !== $isContext && $emailAddresses
            ? $this->loadSuggestedEntities(array_values($entities), $emailAddresses)
            : [];
        if ($emailIds) {
            $assignedEntities = $this->loadAssignedEntities($emailIds, array_keys($entities));
            if ($assignedEntities) {
                $records = $this->mergeEntities($records, $assignedEntities);
            }
        }

        if (false === $isContext && $records) {
            $records = $this->removeIsContextEntities($records);
        }

        if ($excludeCurrentUser && isset($entities[User::class]) && $records) {
            $records = $this->removeCurrentUser($records);
        }

        return $records;
    }

    private function loadAssignedEntities(array $emailIds, array $entities): array
    {
        $qb = $this->activityManager->getLimitedActivityTargetsQueryBuilder(
            Email::class,
            $entities,
            ['id' => $emailIds]
        );
        if (null === $qb) {
            return [];
        }

        $rows = $qb->getQuery()->getArrayResult();

        $result = [];
        $resultMap = [];
        foreach ($rows as $row) {
            $key = $this->buildEntityKey($row);
            if (!isset($resultMap[$key])) {
                $result[] = $row;
                $resultMap[$key] = true;
            }
        }

        return $result;
    }

    private function loadSuggestedEntities(array $searchAliases, ?array $emailAddresses): array
    {
        $searchQuery = $this->searchIndexer->getSimpleSearchQuery(null, null, null, $searchAliases);
        if ($emailAddresses) {
            $criteria = $searchQuery->getCriteria();
            if (\count($emailAddresses) === 1) {
                $criteria->andWhere($criteria->expr()->contains('email', reset($emailAddresses)));
            } else {
                $orParts = [];
                foreach ($emailAddresses as $emailAddress) {
                    $orParts[] = $criteria->expr()->contains('email', $emailAddress);
                }
                $criteria->andWhere($criteria->expr()->orX(...$orParts));
            }
        }

        return $this->convertSearchResultToArray($this->searchIndexer->query($searchQuery));
    }

    private function convertSearchResultToArray(SearchResult $searchResult): array
    {
        $result = [];
        /** @var SearchResultItem $record */
        foreach ($searchResult as $record) {
            $result[] = [
                'id'     => $record->getRecordId(),
                'entity' => $record->getEntityName(),
                'title'  => $record->getSelectedData()['name'],
                'item'   => $record
            ];
        }

        return $result;
    }

    private function mergeEntities(array $suggestedEntities, array $assignedEntities): array
    {
        $result = [];
        $assignedEntityMap = [];
        foreach ($assignedEntities as $i => $entity) {
            $assignedEntityMap[$this->buildEntityKey($entity)] = $i;
            $result[$i] = [
                'id'       => $entity['id'],
                'entity'   => $entity['entity'],
                'title'    => $entity['title'],
                'assigned' => true
            ];
        }
        foreach ($suggestedEntities as $entity) {
            $key = $this->buildEntityKey($entity);
            if (!isset($assignedEntityMap[$key])) {
                $result[] = $entity;
            }
        }

        return $result;
    }

    private function markAssignedEntities(array $suggestedEntities, array $assignedEntities): array
    {
        $assignedEntityMap = [];
        foreach ($assignedEntities as $i => $entity) {
            $assignedEntityMap[$this->buildEntityKey($entity)] = $i;
        }
        foreach ($suggestedEntities as &$entity) {
            $key = $this->buildEntityKey($entity);
            if (isset($assignedEntityMap[$key])) {
                $entity['assigned'] = true;
            }
        }

        return $suggestedEntities;
    }

    private function buildEntityKey(array $entity): string
    {
        return $entity['entity'] . ':' . $entity['id'];
    }

    private function removeIsContextEntities(array $records): array
    {
        $filteredRecords = [];
        foreach ($records as $record) {
            if (!isset($record['assigned']) || true !== $record['assigned']) {
                $filteredRecords[] = $record;
            }
        }

        return $filteredRecords;
    }

    private function removeCurrentUser(array $records): array
    {
        $currentUserId = $this->tokenAccessor->getUserId();
        if (null === $currentUserId) {
            return $records;
        }

        $filteredRecords = [];
        foreach ($records as $record) {
            if (User::class === $record['entity']
                && $currentUserId == $record['id']
                && ($record['assigned'] ?? false) === false
            ) {
                continue;
            }
            $filteredRecords[] = $record;
        }

        return $filteredRecords;
    }

    private function buildResult(array $records, ?int $maxResults, RequestType $requestType): array
    {
        $hasMore = false;
        if (null !== $maxResults && \count($records) > $maxResults) {
            $records = \array_slice($records, 0, $maxResults);
            $hasMore = true;
        }

        $result = [];
        foreach ($records as $record) {
            $item = $record['item'] ?? new SearchResultItem($record['entity'], $record['id']);
            $this->eventDispatcher->dispatch(new PrepareResultItemEvent($item), PrepareResultItemEvent::EVENT_NAME);
            $result[] = $this->createResultItem(
                $this->buildResultItemId($record, $requestType),
                $record['entity'],
                $record['id'],
                $record['title'],
                $item->getRecordUrl(),
                $record['assigned'] ?? false
            );
        }

        if ($hasMore) {
            $result[ConfigUtil::INFO_RECORD_KEY] = [ConfigUtil::HAS_MORE => true];
        }

        return $result;
    }
}
