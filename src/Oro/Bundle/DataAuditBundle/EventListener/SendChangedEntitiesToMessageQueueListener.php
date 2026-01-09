<?php

namespace Oro\Bundle\DataAuditBundle\EventListener;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\PersistentCollection;
use Oro\Bundle\DataAuditBundle\Async\Topic\AuditChangedEntitiesTopic;
use Oro\Bundle\DataAuditBundle\Model\AdditionalEntityChangesToAuditStorage;
use Oro\Bundle\DataAuditBundle\Provider\AuditConfigProvider;
use Oro\Bundle\DataAuditBundle\Provider\AuditMessageBodyProvider;
use Oro\Bundle\DataAuditBundle\Service\EntityToEntityChangeArrayConverter;
use Oro\Bundle\DistributionBundle\Handler\ApplicationState;
use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Bundle\PlatformBundle\EventListener\OptionalListenerInterface;
use Oro\Bundle\PlatformBundle\EventListener\OptionalListenerTrait;
use Oro\Component\MessageQueue\Client\Message;
use Oro\Component\MessageQueue\Client\MessagePriority;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\PhpUtils\ArrayUtil;
use Psr\Log\LoggerInterface;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * The listener does not support next features:
 *
 * Collection::clear - the deletion diff is empty because clear does takeSnapshot internally
 * Collection::removeElement - in case of "fetch extra lazy" does not schedule anything
 * "Doctrine will only check the owning side of an association for changes."
 * http://doctrine-orm.readthedocs.io/projects/doctrine-orm/en/latest/reference/unitofwork-associations.html
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class SendChangedEntitiesToMessageQueueListener implements OptionalListenerInterface
{
    use OptionalListenerTrait;

    private const BATCH_SIZE = 100;

    private \SplObjectStorage $allInsertions;
    private \SplObjectStorage $allUpdates;
    private \SplObjectStorage $allDeletions;
    private \SplObjectStorage $allCollectionUpdates;
    private \SplObjectStorage $allTokens;

    /**
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        private readonly MessageProducerInterface $messageProducer,
        private readonly TokenStorageInterface $tokenStorage,
        private readonly AdditionalEntityChangesToAuditStorage $additionalEntityChangesStorage,
        private readonly EntityToEntityChangeArrayConverter $entityToArrayConverter,
        private readonly AuditConfigProvider $auditConfigProvider,
        private readonly AuditMessageBodyProvider $auditMessageBodyProvider,
        private readonly ApplicationState $applicationState,
        private readonly EntityNameResolver $entityNameResolver,
        private readonly PropertyAccessorInterface $propertyAccessor,
        private readonly LoggerInterface $logger
    ) {
        $this->allInsertions = new \SplObjectStorage();
        $this->allUpdates = new \SplObjectStorage();
        $this->allDeletions = new \SplObjectStorage();
        $this->allCollectionUpdates = new \SplObjectStorage();
        $this->allTokens = new \SplObjectStorage();
    }

    private function isEnabled(): bool
    {
        if (!$this->applicationState->isInstalled()) {
            return false;
        }
        return $this->enabled;
    }

    public function onFlush(OnFlushEventArgs $eventArgs): void
    {
        if (!$this->isEnabled()) {
            return;
        }

        $em = $eventArgs->getObjectManager();

        $this->findAuditableInsertions($em);
        $this->findAuditableUpdates($em);
        $this->findAuditableDeletions($em);
        $this->findAuditableCollectionUpdates($em);

        $token = $this->tokenStorage->getToken();
        if (null !== $token) {
            $this->allTokens[$em] = $token;
        }
    }

    public function postFlush(PostFlushEventArgs $eventArgs): void
    {
        if (!$this->isEnabled()) {
            return;
        }

        $em = $eventArgs->getObjectManager();
        try {
            $insertions = $this->processInsertions($em);
            $updates = $this->processUpdates($em);
            $deletes = $this->processDeletions($em);
            $collectionUpdates = $this->processCollectionUpdates($em, $insertions, $updates, $deletes);

            do {
                $body = $this->auditMessageBodyProvider->prepareMessageBody(
                    array_splice($insertions, 0, self::BATCH_SIZE),
                    array_splice($updates, 0, self::BATCH_SIZE),
                    array_splice($deletes, 0, self::BATCH_SIZE),
                    array_splice($collectionUpdates, 0, self::BATCH_SIZE),
                    $this->getSecurityToken($em)
                );

                if (!empty($body)) {
                    $this->messageProducer->send(
                        AuditChangedEntitiesTopic::getName(),
                        new Message($body, MessagePriority::VERY_LOW)
                    );
                }
            } while ($body);
        } finally {
            $this->allInsertions->detach($em);
            $this->allUpdates->detach($em);
            $this->allDeletions->detach($em);
            $this->allCollectionUpdates->detach($em);
            $this->allTokens->detach($em);
        }
    }

    private function getSecurityToken(EntityManagerInterface $em): ?TokenInterface
    {
        return $this->allTokens->contains($em)
            ? $this->allTokens[$em]
            : $this->tokenStorage->getToken();
    }

    private function findAuditableInsertions(EntityManagerInterface $em): void
    {
        $uow = $em->getUnitOfWork();

        $insertions = new \SplObjectStorage();
        $scheduledInsertions = $uow->getScheduledEntityInsertions();
        foreach ($scheduledInsertions as $entity) {
            if (!$this->auditConfigProvider->isAuditableEntity(ClassUtils::getClass($entity))) {
                continue;
            }

            $insertions[$entity] = $uow->getEntityChangeSet($entity);
        }

        $this->saveChanges($this->allInsertions, $em, $insertions);
    }

    private function findAuditableUpdates(EntityManagerInterface $em): void
    {
        $uow = $em->getUnitOfWork();

        $updates = new \SplObjectStorage();
        $scheduledUpdates = $uow->getScheduledEntityUpdates();
        foreach ($scheduledUpdates as $entity) {
            if (!$this->auditConfigProvider->isAuditableEntity(ClassUtils::getClass($entity))) {
                continue;
            }

            $changeSet = $uow->getEntityChangeSet($entity);
            if (!empty($changeSet) || $this->hasAssociationUpdates($em, $entity)) {
                $updates[$entity] = $changeSet;
            }
        }

        $this->saveChanges($this->allUpdates, $em, $updates);
    }

    private function hasAssociationUpdates(EntityManagerInterface $em, object $entity): bool
    {
        $uow = $em->getUnitOfWork();
        $ownerSplHash = spl_object_hash($entity);

        return
            $this->hasAuditableCollection($uow->getScheduledCollectionDeletions(), $ownerSplHash)
            || $this->hasAuditableCollection($uow->getScheduledCollectionUpdates(), $ownerSplHash);
    }

    private function hasAuditableCollection(array $collections, string $ownerSplHash): bool
    {
        /** @var PersistentCollection $collection */
        foreach ($collections as $collection) {
            if (
                spl_object_hash($collection->getOwner()) === $ownerSplHash
                && $this->auditConfigProvider->isAuditableEntity($collection->getTypeClass()->getName())
            ) {
                return true;
            }
        }

        return false;
    }

    private function findAuditableDeletions(EntityManagerInterface $em): void
    {
        $uow = $em->getUnitOfWork();

        $deletions = new \SplObjectStorage();
        $scheduledDeletions = $uow->getScheduledEntityDeletions();
        foreach ($scheduledDeletions as $entity) {
            $entityClass = ClassUtils::getClass($entity);
            if (!$this->auditConfigProvider->isAuditableEntity($entityClass)) {
                continue;
            }

            $changeSet = [];
            $fields = $this->auditConfigProvider->getAuditableFields($entityClass);
            $classMetadata = $em->getClassMetadata($entityClass);
            $fields[] = $classMetadata->getSingleIdentifierFieldName();
            $originalData = $uow->getOriginalEntityData($entity);
            foreach ($fields as $field) {
                try {
                    $oldValue = $this->propertyAccessor->getValue($entity, $field) ?? $originalData[$field] ?? null;
                    $changeSet[$field] = [$oldValue, null];
                } catch (NoSuchPropertyException $e) {
                }
            }

            $entityName = $this->entityNameResolver->getName($entity);
            $deletion = $this->convertEntityToArray($em, $entity, $changeSet, $entityName);

            $deletions[$entity] = $deletion;

            if (null === $deletion['entity_id']) {
                $this->logger->error(
                    sprintf('The entity "%s" has an empty id.', $deletion['entity_class']),
                    ['entity' => $entity, 'deletion' => $deletion]
                );
            }
        }

        $this->saveChanges($this->allDeletions, $em, $deletions);
    }

    private function findAuditableCollectionUpdates(EntityManagerInterface $em): void
    {
        $uow = $em->getUnitOfWork();
        $collectionUpdates = new \SplObjectStorage();

        $scheduledCollectionUpdates = $uow->getScheduledCollectionUpdates();
        $collectionDeletions = $this->findAuditableCollectionDeletions($em);
        foreach ($scheduledCollectionUpdates as $updateCollection) {
            if (!$this->auditConfigProvider->isAuditableEntity($updateCollection->getTypeClass()->getName())) {
                continue;
            }

            $deleteDiff = [];
            if ($collectionDeletions->offsetExists($updateCollection)) {
                $deleteDiff = $collectionDeletions[$updateCollection]['deleteDiff'];
                $collectionDeletions->detach($updateCollection);
            }

            $collectionUpdates[$updateCollection] = [
                'insertDiff' => $updateCollection->getInsertDiff(),
                'deleteDiff' => array_merge($updateCollection->getDeleteDiff(), $deleteDiff),
                'changeDiff' => array_filter(
                    $updateCollection->toArray(),
                    function ($entity) use ($uow, $updateCollection) {
                        return
                            $uow->isScheduledForUpdate($entity)
                            && !\in_array($entity, $updateCollection->getInsertDiff(), true)
                            && !\in_array($entity, $updateCollection->getDeleteDiff(), true);
                    }
                ),
            ];
        }

        $this->saveChanges($this->allCollectionUpdates, $em, $collectionUpdates);
        $this->saveChanges($this->allCollectionUpdates, $em, $collectionDeletions);
    }

    private function findAuditableCollectionDeletions(EntityManagerInterface $em): \SplObjectStorage
    {
        $uow = $em->getUnitOfWork();
        $collectionDeletions = new \SplObjectStorage();

        $scheduledCollectionDeletions = $uow->getScheduledCollectionDeletions();
        foreach ($scheduledCollectionDeletions as $collection) {
            if (!$this->auditConfigProvider->isAuditableEntity($collection->getTypeClass()->getName())) {
                continue;
            }

            $mapping = $collection->getMapping();
            $identityMap = $uow->getIdentityMap();
            $targetEntityName = $mapping['targetEntity'];
            $isOwningSide = $mapping['isOwningSide'];
            if ($isOwningSide && \array_key_exists($targetEntityName, $identityMap)) {
                $deletionEntitiesDiff = array_udiff(
                    $identityMap[$targetEntityName],
                    $collection->toArray(),
                    fn ($obj1, $obj2) => strcmp(spl_object_hash($obj1), spl_object_hash($obj2))
                );
            } else {
                $deletionEntitiesDiff = $collection->toArray();
            }

            $collectionDeletions[$collection] = [
                'insertDiff' => [],
                'deleteDiff' => $deletionEntitiesDiff,
                'changeDiff' => []
            ];
        }

        return $collectionDeletions;
    }

    private function saveChanges(
        \SplObjectStorage $storage,
        EntityManagerInterface $em,
        \SplObjectStorage $changes
    ): void {
        if ($changes->count() > 0) {
            if (!$storage->contains($em)) {
                $storage[$em] = $changes;
            } else {
                $previousChangesInCurrentTransaction = $storage[$em];
                $changes->addAll($previousChangesInCurrentTransaction);
                $storage[$em] = $changes;
            }
        }
    }

    private function processInsertions(EntityManagerInterface $em): array
    {
        if (!$this->allInsertions->contains($em)) {
            return [];
        }

        $insertions = [];
        foreach ($this->allInsertions[$em] as $entity) {
            $changeSet = $this->allInsertions[$em][$entity];
            $insertions[spl_object_hash($entity)] = $this->convertEntityToArray($em, $entity, $changeSet);
        }

        return $insertions;
    }

    private function processUpdates(EntityManagerInterface $em): array
    {
        $updates = $this->getUpdates($em);

        if (!$this->additionalEntityChangesStorage->hasEntityUpdates($em)) {
            return $updates;
        }

        $additionalUpdates = $this->additionalEntityChangesStorage->getEntityUpdates($em);
        foreach ($additionalUpdates as $entity) {
            $changeSet = $additionalUpdates->offsetGet($entity);
            $additionalUpdate = $this->processUpdate($em, $entity, $changeSet);
            if (!$additionalUpdate) {
                continue;
            }

            $key = spl_object_hash($entity);
            if (\array_key_exists($key, $updates)) {
                $updates[$key]['change_set'] = array_merge(
                    (array)$updates[$key]['change_set'],
                    $additionalUpdate['change_set'] ?? []
                );
            } else {
                $updates[spl_object_hash($entity)] = $additionalUpdate;
            }
        }
        $this->additionalEntityChangesStorage->clear($em);

        return $updates;
    }

    private function getUpdates(EntityManagerInterface $em): array
    {
        $updates = [];
        if ($this->allUpdates->contains($em)) {
            foreach ($this->allUpdates[$em] as $entity) {
                $changeSet = $this->allUpdates[$em][$entity];
                $update = $this->processUpdate($em, $entity, $changeSet);
                if (!$update) {
                    continue;
                }

                $updates[spl_object_hash($entity)] = $update;
            }
        }

        return $updates;
    }

    private function processUpdate(EntityManagerInterface $entityManager, object $entity, array $changeSet): ?array
    {
        $update = $this->convertEntityToArray($entityManager, $entity, $changeSet);
        if (null !== $update['entity_id']) {
            return $update;
        }

        $this->logger->error(
            sprintf('The entity "%s" has an empty id.', $update['entity_class']),
            ['entity' => $entity, 'update' => $update]
        );

        return null;
    }

    private function processDeletions(EntityManagerInterface $em): array
    {
        if (!$this->allDeletions->contains($em)) {
            return [];
        }

        $deletions = [];
        foreach ($this->allDeletions[$em] as $entity) {
            $deletions[spl_object_hash($entity)] = $this->allDeletions[$em][$entity];
        }

        return $deletions;
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function processCollectionUpdates(
        EntityManagerInterface $em,
        array $insertions = [],
        array $updates = [],
        array $deletions = []
    ): array {
        if (!$this->allCollectionUpdates->contains($em)) {
            return [];
        }

        $collectionUpdates = [];
        /** @var PersistentCollection $collection */
        foreach ($this->allCollectionUpdates[$em] as $collection) {
            $inserted = [];
            $deleted = [];
            $changed = [];

            foreach ($this->allCollectionUpdates[$em][$collection]['insertDiff'] as $entity) {
                $entityHash = spl_object_hash($entity);
                $inserted[$entityHash] = $insertions[$entityHash] ?? $this->convertEntityToArray($em, $entity, []);
            }

            foreach ($this->allCollectionUpdates[$em][$collection]['deleteDiff'] as $entity) {
                $entityHash = spl_object_hash($entity);
                $deleted[$entityHash] = $deletions[$entityHash] ?? $this->convertEntityToArray($em, $entity, []);
            }

            foreach ($this->allCollectionUpdates[$em][$collection]['changeDiff'] as $entity) {
                $entityHash = spl_object_hash($entity);
                $changed[$entityHash] = $updates[$entityHash] ?? $this->convertEntityToArray($em, $entity, []);
            }

            $ownerFieldName = $collection->getMapping()['fieldName'];
            $entityData = $this->convertEntityToArray($em, $collection->getOwner(), []);
            $entityData['change_set'][$ownerFieldName] = [
                ['deleted' => $deleted],
                ['inserted' => $inserted, 'changed' => $changed],
            ];

            if ($inserted || $deleted || $changed) {
                $key = spl_object_hash($collection->getOwner());
                $collectionUpdates[$key] = \array_key_exists($key, $collectionUpdates)
                    ? ArrayUtil::arrayMergeRecursiveDistinct($collectionUpdates[$key], $entityData)
                    : $entityData;
            }
        }

        return $collectionUpdates;
    }

    private function convertEntityToArray(
        EntityManagerInterface $em,
        object $entity,
        array $changeSet,
        ?string $entityName = null
    ): array {
        return $this->entityToArrayConverter->convertNamedEntityToArray($em, $entity, $changeSet, $entityName);
    }
}
