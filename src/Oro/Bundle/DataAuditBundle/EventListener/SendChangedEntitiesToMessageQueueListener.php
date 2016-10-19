<?php
namespace Oro\Bundle\DataAuditBundle\EventListener;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\ORM\PersistentCollection;
use Oro\Bundle\DataAuditBundle\Async\Topics;
use Oro\Bundle\DataAuditBundle\Provider\AuditConfigProvider;
use Oro\Bundle\DataAuditBundle\Service\EntityToEntityChangeArrayConverter;
use Oro\Bundle\PlatformBundle\EventListener\OptionalListenerInterface;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationContextTokenInterface;
use Oro\Bundle\SecurityBundle\Tools\UUIDGenerator;
use Oro\Bundle\UserBundle\Entity\AbstractUser;
use Oro\Component\MessageQueue\Client\Message;
use Oro\Component\MessageQueue\Client\MessagePriority;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * The listener does not support next features:
 *
 * Collection::clear - the deletion diff is empty because clear does takeSnapshot internally
 * Collection::removeElement - in case of "fetch extra lazy" does not schedule anything
 * "Doctrine will only check the owning side of an association for changes."
 * http://doctrine-orm.readthedocs.io/projects/doctrine-orm/en/latest/reference/unitofwork-associations.html
 */
class SendChangedEntitiesToMessageQueueListener implements OptionalListenerInterface
{
    /**
     * @var MessageProducerInterface
     */
    private $messageProducer;

    /**
     * @var TokenStorageInterface
     */
    private $securityTokenStorage;

    /**
     * @var EntityToEntityChangeArrayConverter
     */
    private $entityToArrayConverter;

    /**
     * @var AuditConfigProvider
     */
    private $configProvider;

    /**
     * @var \SplObjectStorage
     */
    private $allInsertions;

    /**
     * @var \SplObjectStorage
     */
    private $allUpdates;

    /**
     * @var \SplObjectStorage
     */
    private $allDeletions;

    /**
     * @var \SplObjectStorage
     */
    private $allCollectionUpdates;

    /**
     * @var boolean
     */
    private $enabled = true;

    /**
     * @param MessageProducerInterface $messageProducer
     * @param TokenStorageInterface $securityTokenStorage
     * @param EntityToEntityChangeArrayConverter $entityToArrayConverter
     * @param AuditConfigProvider $configProvider
     */
    public function __construct(
        MessageProducerInterface $messageProducer,
        TokenStorageInterface $securityTokenStorage,
        EntityToEntityChangeArrayConverter $entityToArrayConverter,
        AuditConfigProvider $configProvider
    ) {
        $this->messageProducer = $messageProducer;
        $this->securityTokenStorage = $securityTokenStorage;
        $this->entityToArrayConverter = $entityToArrayConverter;
        $this->configProvider = $configProvider;

        $this->allInsertions = new \SplObjectStorage;
        $this->allUpdates = new \SplObjectStorage;
        $this->allDeletions = new \SplObjectStorage;
        $this->allCollectionUpdates = new \SplObjectStorage;
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

        if (!$this->hasChanges($this->allInsertions, $em)
            && !$this->hasChanges($this->allUpdates, $em)
            && !$this->hasChanges($this->allDeletions, $em)
            && !$this->hasChanges($this->allCollectionUpdates, $em)
        ) {
            return;
        }

        try {
            $body = [
                'timestamp' => time(),
                'transaction_id' => UUIDGenerator::v4(),
                'entities_updated' => [],
                'entities_inserted' => [],
                'entities_deleted' => [],
                'collections_updated' => [],
            ];

            $securityToken = $this->securityTokenStorage->getToken();
            if (null !== $securityToken) {
                $user = $securityToken->getUser();
                if ($user instanceof AbstractUser) {
                    $body['user_id'] = $user->getId();
                    $body['user_class'] = ClassUtils::getClass($user);
                }
                if ($securityToken instanceof OrganizationContextTokenInterface) {
                    $body['organization_id'] = $securityToken->getOrganizationContext()->getId();
                }
            }

            $body['entities_inserted'] = $this->processInsertions($em);
            $body['entities_updated'] = $this->processUpdates($em);
            $body['entities_deleted'] = $this->processDeletions($em);
            $body['collections_updated'] = $this->processCollectionUpdates($em);

            $message = new Message();
            $message->setPriority(MessagePriority::VERY_LOW);
            $message->setBody($body);

            $this->messageProducer->send(Topics::ENTITIES_CHANGED, $message);
        } finally {
            $this->allInsertions->detach($em);
            $this->allUpdates->detach($em);
            $this->allDeletions->detach($em);
            $this->allCollectionUpdates->detach($em);
        }
    }

    /**
     * @param EntityManager $em
     */
    private function findAuditableInsertions(EntityManager $em)
    {
        $uow = $em->getUnitOfWork();

        $insertions = new \SplObjectStorage();
        foreach ($uow->getScheduledEntityInsertions() as $entity) {
            if (!$this->configProvider->isAuditableEntity(ClassUtils::getClass($entity))) {
                continue;
            }

            $insertions[$entity] = $uow->getEntityChangeSet($entity);
        }

        $this->allInsertions[$em] = $insertions;
    }

    /**
     * @param EntityManager $em
     */
    private function findAuditableUpdates(EntityManager $em)
    {
        $uow = $em->getUnitOfWork();

        $updates = new \SplObjectStorage();
        foreach ($uow->getScheduledEntityUpdates() as $entity) {
            if (!$this->configProvider->isAuditableEntity(ClassUtils::getClass($entity))) {
                continue;
            }

            $updates[$entity] = $uow->getEntityChangeSet($entity);
        }

        $this->allUpdates[$em] = $updates;
    }

    /**
     * @param EntityManager $em
     */
    private function findAuditableDeletions(EntityManager $em)
    {
        $uow = $em->getUnitOfWork();

        $deletions = new \SplObjectStorage();
        foreach ($uow->getScheduledEntityDeletions() as $entity) {
            $entityClass = ClassUtils::getClass($entity);
            if (!$this->configProvider->isAuditableEntity($entityClass)) {
                continue;
            }

            $changeSet = [];
            $entityMeta = $em->getClassMetadata($entityClass);

            // in order to audit many to one inverse side we have to store some info to change set.
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

            $deletions[$entity] = $this->convertEntityToArray($em, $entity, $changeSet);
        }

        $this->allDeletions[$em] = $deletions;
    }

    /**
     * @param EntityManager $em
     */
    private function findAuditableCollectionUpdates(EntityManager $em)
    {
        $uow = $em->getUnitOfWork();

        $collectionUpdates = new \SplObjectStorage();
        foreach ($uow->getScheduledCollectionUpdates() as $collection) {
            /** @var $collection PersistentCollection */
            if (!$this->configProvider->isAuditableEntity($collection->getTypeClass()->getName())) {
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
        $this->allCollectionUpdates[$em] = $collectionUpdates;
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
        if (!$this->allUpdates->contains($em)) {
            return [];
        }

        $updates = [];
        foreach ($this->allUpdates[$em] as $entity) {
            $changeSet = $this->allUpdates[$em][$entity];
            $updates[] = $this->convertEntityToArray($em, $entity, $changeSet);
        }

        return $updates;
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
                'deleted' => [],
                'changed' => []
            ];
            foreach ($this->allCollectionUpdates[$em][$collection]['insertDiff'] as $entity) {
                $new['inserted'][] = $this->convertEntityToArray($em, $entity, []);
            }
            foreach ($this->allCollectionUpdates[$em][$collection]['deleteDiff'] as $entity) {
                $new['deleted'][] = $entity;
            }

            $ownerFieldName = $collection->getMapping()['fieldName'];
            $entityData  = $this->convertEntityToArray($em, $collection->getOwner(), []);
            $entityData['change_set'][$ownerFieldName] = [null, $new];

            if ($new['inserted'] || $new['deleted']) {
                $collectionUpdates[] = $entityData;
            }
        }

        return $collectionUpdates;
    }

    /**
     * @param \SplObjectStorage $storage
     * @param EntityManager     $em
     *
     * @return bool
     */
    private function hasChanges(\SplObjectStorage $storage, EntityManager $em)
    {
        return $storage->contains($em) && count($storage[$em]) > 0;
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
