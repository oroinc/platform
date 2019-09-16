<?php

namespace Oro\Bundle\DataAuditBundle\EventListener;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\ORM\PersistentCollection;
use Oro\Bundle\DataAuditBundle\Async\Topics;
use Oro\Bundle\DataAuditBundle\Model\AdditionalEntityChangesToAuditStorage;
use Oro\Bundle\DataAuditBundle\Provider\AuditConfigProvider;
use Oro\Bundle\DataAuditBundle\Provider\AuditMessageBodyProvider;
use Oro\Bundle\DataAuditBundle\Service\EntityToEntityChangeArrayConverter;
use Oro\Bundle\PlatformBundle\EventListener\OptionalListenerInterface;
use Oro\Component\MessageQueue\Client\Message;
use Oro\Component\MessageQueue\Client\MessagePriority;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Psr\Log\LoggerInterface;
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
    private const BATCH_SIZE = 100;

    /** @var MessageProducerInterface */
    private $messageProducer;

    /** @var TokenStorageInterface */
    private $tokenStorage;

    /** @var EntityToEntityChangeArrayConverter */
    private $entityToArrayConverter;

    /** @var AuditConfigProvider */
    private $auditConfigProvider;

    /** @var LoggerInterface */
    private $logger;

    /** @var bool */
    private $enabled = true;

    /** @var \SplObjectStorage */
    private $allInsertions;

    /** @var \SplObjectStorage */
    private $allUpdates;

    /** @var \SplObjectStorage */
    private $allDeletions;

    /** @var \SplObjectStorage */
    private $allCollectionUpdates;

    /** @var \SplObjectStorage */
    private $allTokens;

    /** @var AdditionalEntityChangesToAuditStorage */
    private $additionalEntityChangesStorage;

    /** @var AuditMessageBodyProvider */
    private $auditMessageBodyProvider;

    /**
     * @param MessageProducerInterface $messageProducer
     * @param TokenStorageInterface $tokenStorage
     * @param AdditionalEntityChangesToAuditStorage $additionalEntityChangesStorage
     * @param EntityToEntityChangeArrayConverter $entityToArrayConverter
     * @param AuditConfigProvider $auditConfigProvider
     * @param LoggerInterface $logger
     * @param AuditMessageBodyProvider $auditMessageBodyProvider
     */
    public function __construct(
        MessageProducerInterface $messageProducer,
        TokenStorageInterface $tokenStorage,
        AdditionalEntityChangesToAuditStorage $additionalEntityChangesStorage,
        EntityToEntityChangeArrayConverter $entityToArrayConverter,
        AuditConfigProvider $auditConfigProvider,
        LoggerInterface $logger,
        AuditMessageBodyProvider $auditMessageBodyProvider
    ) {
        $this->messageProducer = $messageProducer;
        $this->tokenStorage = $tokenStorage;
        $this->additionalEntityChangesStorage = $additionalEntityChangesStorage;
        $this->entityToArrayConverter = $entityToArrayConverter;
        $this->auditConfigProvider = $auditConfigProvider;
        $this->logger = $logger;
        $this->auditMessageBodyProvider = $auditMessageBodyProvider;

        $this->allInsertions = new \SplObjectStorage;
        $this->allUpdates = new \SplObjectStorage;
        $this->allDeletions = new \SplObjectStorage;
        $this->allCollectionUpdates = new \SplObjectStorage;
        $this->allTokens = new \SplObjectStorage;
    }

    /**
     * {@inheritdoc}
     */
    public function setEnabled($enabled = true)
    {
        $this->enabled = $enabled;
    }

    /**
     * @param OnFlushEventArgs $eventArgs
     */
    public function onFlush(OnFlushEventArgs $eventArgs)
    {
        if (!$this->enabled) {
            return;
        }

        $em = $eventArgs->getEntityManager();

        $this->findAuditableInsertions($em);
        $this->findAuditableUpdates($em);
        $this->findAuditableDeletions($em);
        $this->findAuditableCollectionUpdates($em);

        $token = $this->tokenStorage->getToken();
        if (null !== $token) {
            $this->allTokens[$em] = $token;
        }
    }

    /**
     * @param PostFlushEventArgs $eventArgs
     */
    public function postFlush(PostFlushEventArgs $eventArgs)
    {
        if (!$this->enabled) {
            return;
        }

        $em = $eventArgs->getEntityManager();
        try {
            $insertions = $this->processInsertions($em);
            $updates = $this->processUpdates($em);
            $deletes = $this->processDeletions($em);
            $collectionUpdates = $this->processCollectionUpdates($em);

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
                        Topics::ENTITIES_CHANGED,
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

    /**
     * @param EntityManager $em
     *
     * @return TokenInterface|null
     */
    private function getSecurityToken(EntityManager $em)
    {
        return $this->allTokens->contains($em)
            ? $this->allTokens[$em]
            : $this->tokenStorage->getToken();
    }

    /**
     * @param EntityManager $em
     */
    private function findAuditableInsertions(EntityManager $em)
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

    /**
     * @param EntityManager $em
     */
    private function findAuditableUpdates(EntityManager $em)
    {
        $uow = $em->getUnitOfWork();

        $updates = new \SplObjectStorage();
        $scheduledUpdates = $uow->getScheduledEntityUpdates();
        foreach ($scheduledUpdates as $entity) {
            if (!$this->auditConfigProvider->isAuditableEntity(ClassUtils::getClass($entity))) {
                continue;
            }

            $updates[$entity] = $uow->getEntityChangeSet($entity);
        }

        $this->saveChanges($this->allUpdates, $em, $updates);
    }

    /**
     * @param EntityManager $em
     */
    private function findAuditableDeletions(EntityManager $em)
    {
        $uow = $em->getUnitOfWork();

        $deletions = new \SplObjectStorage();
        $scheduledDeletions = $uow->getScheduledEntityDeletions();
        foreach ($scheduledDeletions as $entity) {
            $entityClass = ClassUtils::getClass($entity);
            if (!$this->auditConfigProvider->isAuditableEntity($entityClass)) {
                continue;
            }

            $deletion = $this->convertEntityToArray($em, $entity, []);

            // in order to audit many to one inverse side we have to store some info to change set.
            $changeSet = [];
            $entityMeta = $em->getClassMetadata($entityClass);
            foreach ($entityMeta->associationMappings as $filedName => $mapping) {
                if (ClassMetadataInfo::MANY_TO_ONE === $mapping['type']) {
                    $relatedEntity = $entityMeta->getFieldValue($entity, $filedName);
                    if ($relatedEntity) {
                        $changeSet[$filedName] = [
                            $this->convertEntityToArray($em, $relatedEntity, []),
                            null
                        ];
                    }
                }
            }
            if (!empty($changeSet)) {
                $deletion['change_set'] = $changeSet;
            }

            if (null === $deletion['entity_id']) {
                $this->logger->error(
                    sprintf('The entity "%s" has an empty id.', $deletion['entity_class']),
                    ['entity' => $entity, 'deletion' => $deletion]
                );
                if (!empty($deletion['change_set'])) {
                    $deletions[$entity] = $deletion;
                }
            } else {
                $deletions[$entity] = $deletion;
            }
        }

        $this->saveChanges($this->allDeletions, $em, $deletions);
    }

    /**
     * @param EntityManager $em
     */
    private function findAuditableCollectionUpdates(EntityManager $em)
    {
        $uow = $em->getUnitOfWork();

        $collectionUpdates = new \SplObjectStorage();
        /** @var PersistentCollection[] $scheduledCollectionUpdates */
        $scheduledCollectionUpdates = $uow->getScheduledCollectionUpdates();
        foreach ($scheduledCollectionUpdates as $collection) {
            if (!$this->auditConfigProvider->isAuditableEntity($collection->getTypeClass()->getName())) {
                continue;
            }

            $insertDiff = $collection->getInsertDiff();
            $deleteDiff = [];
            foreach ($collection->getDeleteDiff() as $deletedEntity) {
                $deleteDiff[] = $this->convertEntityToArray($em, $deletedEntity, []);
            }

            $collectionUpdates[$collection] = [
                'insertDiff' => $insertDiff,
                'deleteDiff' => $deleteDiff,
            ];
        }

        $this->saveChanges($this->allCollectionUpdates, $em, $collectionUpdates);
    }

    /**
     * @param \SplObjectStorage $storage
     * @param EntityManager     $em
     * @param \SplObjectStorage $changes
     */
    private function saveChanges(\SplObjectStorage $storage, EntityManager $em, \SplObjectStorage $changes)
    {
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

    /**
     * @param EntityManager $em
     *
     * @return array
     */
    private function processInsertions(EntityManager $em)
    {
        if (!$this->allInsertions->contains($em)) {
            return [];
        }

        $insertions = [];
        foreach ($this->allInsertions[$em] as $entity) {
            $changeSet = $this->allInsertions[$em][$entity];
            $insertions[] = $this->convertEntityToArray($em, $entity, $changeSet);
        }

        return $insertions;
    }

    /**
     * @param EntityManager $em
     *
     * @return array
     */
    private function processUpdates(EntityManager $em)
    {
        $updates = $this->getUpdates($em);

        if ($this->additionalEntityChangesStorage->hasEntityUpdates($em)) {
            $additionalUpdates = $this->additionalEntityChangesStorage->getEntityUpdates($em);
            foreach ($additionalUpdates as $entity) {
                $additionalUpdate = $this->processUpdate($em, $entity, $additionalUpdates->offsetGet($entity));
                if ($additionalUpdate) {
                    $existingUpdateKey = $this->findUpdate($updates, $additionalUpdate);
                    if (null === $existingUpdateKey) {
                        $updates[] = $additionalUpdate;
                    } elseif (!empty($additionalUpdate['change_set'])) {
                        if (empty($updates[$existingUpdateKey]['change_set'])) {
                            $updates[$existingUpdateKey]['change_set'] = $additionalUpdate['change_set'];
                        } else {
                            $updates[$existingUpdateKey]['change_set'] = array_merge(
                                $updates[$existingUpdateKey]['change_set'],
                                $additionalUpdate['change_set']
                            );
                        }
                    }
                }
            }
            $this->additionalEntityChangesStorage->clear($em);
        }

        return $updates;
    }

    /**
     * @param EntityManager $em
     *
     * @return array
     */
    private function getUpdates(EntityManager $em): array
    {
        $updates = [];
        if ($this->allUpdates->contains($em)) {
            foreach ($this->allUpdates[$em] as $entity) {
                $update = $this->processUpdate($em, $entity, $this->allUpdates[$em][$entity]);
                if ($update) {
                    $updates[] = $update;
                }
            }
        }

        return $updates;
    }

    /**
     * @param array $updates
     * @param array $additionalUpdate
     *
     * @return int|null The key of the found update or NULL
     */
    private function findUpdate(array $updates, array $additionalUpdate)
    {
        $foundKey = null;
        foreach ($updates as $key => $update) {
            if ($update['entity_id'] === $additionalUpdate['entity_id']
                && $update['entity_class'] === $additionalUpdate['entity_class']
            ) {
                $foundKey = $key;
                break;
            }
        }

        return $foundKey;
    }

    /**
     * @param EntityManager $entityManager
     * @param object        $entity
     * @param array         $changeSet
     *
     * @return array|null
     */
    private function processUpdate(EntityManager $entityManager, $entity, array $changeSet)
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

    /**
     * @param EntityManager $em
     *
     * @return array
     */
    private function processDeletions(EntityManager $em)
    {
        if (!$this->allDeletions->contains($em)) {
            return [];
        }

        $deletions = [];
        foreach ($this->allDeletions[$em] as $entity) {
            $deletions[] = $this->allDeletions[$em][$entity];
        }

        return $deletions;
    }

    /**
     * @param EntityManager $em
     *
     * @return array
     */
    private function processCollectionUpdates(EntityManager $em)
    {
        if (!$this->allCollectionUpdates->contains($em)) {
            return [];
        }

        $collectionUpdates = [];
        /** @var PersistentCollection $collection */
        foreach ($this->allCollectionUpdates[$em] as $collection) {
            $new = [
                'inserted' => [],
                'deleted'  => [],
                'changed'  => []
            ];
            foreach ($this->allCollectionUpdates[$em][$collection]['insertDiff'] as $entity) {
                $new['inserted'][] = $this->convertEntityToArray($em, $entity, []);
            }
            foreach ($this->allCollectionUpdates[$em][$collection]['deleteDiff'] as $entity) {
                $new['deleted'][] = $entity;
            }

            $ownerFieldName = $collection->getMapping()['fieldName'];
            $entityData = $this->convertEntityToArray($em, $collection->getOwner(), []);
            $entityData['change_set'][$ownerFieldName] = [null, $new];

            if ($new['inserted'] || $new['deleted']) {
                $collectionUpdates[] = $entityData;
            }
        }

        return $collectionUpdates;
    }

    /**
     * @param EntityManager $em
     * @param object        $entity
     * @param array         $changeSet
     *
     * @return array
     */
    private function convertEntityToArray(EntityManager $em, $entity, array $changeSet)
    {
        return $this->entityToArrayConverter->convertEntityToArray($em, $entity, $changeSet);
    }
}
