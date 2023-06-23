<?php

namespace Oro\Bundle\EmailBundle\Api\Processor;

use Doctrine\Common\Collections\Criteria;
use Oro\Bundle\ActivityBundle\Manager\ActivityManager;
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
use Oro\Bundle\EmailBundle\Api\Model\EmailContextItem;
use Oro\Bundle\EmailBundle\Api\SearchEntityListFilterHelper;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailRecipient;
use Oro\Bundle\EmailBundle\Tools\EmailAddressHelper;
use Oro\Bundle\SearchBundle\Engine\Indexer as SearchIndexer;
use Oro\Bundle\SearchBundle\Event\PrepareResultItemEvent;
use Oro\Bundle\SearchBundle\Query\Result as SearchResult;
use Oro\Bundle\SearchBundle\Query\Result\Item as SearchResultItem;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Loads data for the email context API resource.
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class LoadEmailContextItems implements ProcessorInterface
{
    private DoctrineHelper $doctrineHelper;
    private SearchIndexer $searchIndexer;
    private SearchEntityListFilterHelper $searchEntityListFilterHelper;
    private ActivityManager $activityManager;
    private ValueNormalizer $valueNormalizer;
    private EventDispatcherInterface $eventDispatcher;
    private EmailAddressHelper $emailAddressHelper;
    private TokenAccessorInterface $tokenAccessor;

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
        $messageId = $this->getRequestedMessageId($context);
        $emailAddresses = $this->getRequestedEmailAddresses($context->getFilterValues());
        $excludeCurrentUser = $context->getFilterValues()->get('excludeCurrentUser')?->getValue();
        $isContext = $context->getFilterValues()->get('isContext')?->getValue();
        $searchText = $this->getRequestedSearchText(
            $context,
            empty($emailAddresses) && null === $excludeCurrentUser && null === $isContext
        );
        if ($context->hasErrors()) {
            return;
        }

        if (!$entities) {
            $this->setEmptyResult($context);

            return;
        }

        if ($searchText) {
            $this->loadAndSetResultBySearchText($context, $criteria, $entities, $messageId, $searchText);
        } else {
            $this->loadAndSetResult(
                $context,
                $criteria,
                $entities,
                $messageId,
                $emailAddresses,
                $excludeCurrentUser ?? false,
                $isContext
            );
        }
    }

    private function getRequestedMessageId(Context $context): ?string
    {
        $filterValue = $context->getFilterValues()->get('messageId');
        $messageId = $filterValue?->getValue();
        if (!$messageId) {
            $filterKey = $filterValue?->getSourceKey();
            if (!$filterKey) {
                $filterGroup = $context->getFilters()->getDefaultGroupName();
                $filterKey = $filterGroup
                    ? $context->getFilters()->getGroupedFilterKey($filterGroup, 'messageId')
                    : 'messageId';
            }
            $context->addError(
                Error::createValidationError(Constraint::FILTER, 'The Message-ID is required.')
                    ->setSource(ErrorSource::createByParameter($filterKey))
            );
        }

        return $messageId;
    }

    private function getRequestedEmailAddresses(FilterValueAccessorInterface $filterValues): array
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

    private function getRequestedSearchText(Context $context, bool $isFilterBySearchTextAllowed): ?string
    {
        $filterValue = $context->getFilterValues()->get('searchText');
        if (null === $filterValue) {
            return null;
        }

        $searchText = $filterValue->getValue();
        if ($searchText && !$isFilterBySearchTextAllowed) {
            $context->addError(
                Error::createValidationError(Constraint::FILTER, 'The search text cannot be specified together with'
                    . ' "from", "to", "cc", "isContext" or "excludeCurrentUser" filters.')
                    ->setSource(ErrorSource::createByParameter($filterValue->getSourceKey()))
            );
        }

        return $searchText;
    }

    private function getEmailAddresses(array $requestedEmailAddresses, array $existingEmailAddresses): array
    {
        if (!$existingEmailAddresses) {
            return $requestedEmailAddresses;
        }

        return array_values(array_unique(array_merge($requestedEmailAddresses, $existingEmailAddresses)));
    }

    private function findEmailIdByMessageId(string $messageId): ?int
    {
        $rows = $this->doctrineHelper->createQueryBuilder(Email::class, 'e')
            ->select('e.id')
            ->where('e.messageId = :messageId')
            ->setParameter('messageId', $messageId)
            ->getQuery()
            ->getArrayResult();
        if (!$rows) {
            return null;
        }

        return $rows[0]['id'];
    }

    /**
     * @param string $messageId
     *
     * @return array [email id, [email address, ...]]
     */
    private function findEmailIdAndItsAddressesByMessageId(string $messageId): array
    {
        $rows = $this->doctrineHelper->createQueryBuilder(Email::class, 'e')
            ->select('e.id, from_addr.email AS f, to_addr.email AS t')
            ->leftJoin('e.fromEmailAddress', 'from_addr')
            ->leftJoin('e.recipients', 'recipients')
            ->leftJoin('recipients.emailAddress', 'to_addr')
            ->where('e.messageId = :messageId AND recipients.type <> :bcc')
            ->setParameter('messageId', $messageId)
            ->setParameter('bcc', EmailRecipient::BCC)
            ->getQuery()
            ->getArrayResult();
        if (!$rows) {
            return [null, []];
        }

        $emailAddresses = [];
        foreach ($rows as $row) {
            $emailAddress = $row['f'];
            if ($emailAddress && !isset($emailAddresses[$emailAddress])) {
                $emailAddresses[$emailAddress] = true;
            }
            $emailAddress = $row['t'];
            if ($emailAddress && !isset($emailAddresses[$emailAddress])) {
                $emailAddresses[$emailAddress] = true;
            }
        }

        return [$rows[0]['id'], array_keys($emailAddresses)];
    }

    private function setEmptyResult(ListContext $context): void
    {
        $context->setResult([]);
        $context->setTotalCountCallback(function () {
            return 0;
        });
    }

    private function loadAndSetResult(
        ListContext $context,
        Criteria $criteria,
        array $entities,
        string $messageId,
        array $emailAddresses,
        bool $excludeCurrentUser,
        ?bool $isContext
    ): void {
        [$emailId, $existingEmailAddresses] = $this->findEmailIdAndItsAddressesByMessageId($messageId);

        $records = $this->loadData(
            $entities,
            $this->getEmailAddresses($emailAddresses, $existingEmailAddresses),
            $excludeCurrentUser,
            $isContext,
            $emailId
        );

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

    private function loadAndSetResultBySearchText(
        ListContext $context,
        Criteria $criteria,
        array $entities,
        string $messageId,
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

        $emailId = $this->findEmailIdByMessageId($messageId);
        if (null !== $emailId) {
            $assignedEntities = $this->loadAssignedEntities($emailId, array_keys($entities));
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
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function loadData(
        array $entities,
        array $emailAddresses,
        bool $excludeCurrentUser,
        ?bool $isContext,
        ?int $emailId
    ): array {
        $records = true !== $isContext && $emailAddresses
            ? $this->loadSuggestedEntities(array_values($entities), $emailAddresses)
            : [];
        if (null !== $emailId) {
            $assignedEntities = $this->loadAssignedEntities($emailId, array_keys($entities));
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

    private function loadAssignedEntities(int $emailId, array $entities): array
    {
        $qb = $this->activityManager->getLimitedActivityTargetsQueryBuilder(
            Email::class,
            $entities,
            ['id' => $emailId]
        );
        if (null === $qb) {
            return [];
        }

        return $qb->getQuery()->getArrayResult();
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
            $entityClass = $record['entity'];
            $entityId = $record['id'];
            $result[] = new EmailContextItem(
                sprintf(
                    '%s-%s',
                    ValueNormalizerUtil::convertToEntityType($this->valueNormalizer, $entityClass, $requestType),
                    $entityId
                ),
                $entityClass,
                $entityId,
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
