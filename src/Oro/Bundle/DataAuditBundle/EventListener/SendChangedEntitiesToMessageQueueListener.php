<?php
namespace Oro\Bundle\DataAuditBundle\EventListener;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\ORM\PersistentCollection;
use Oro\Bundle\DataAuditBundle\Async\Topics;
use Oro\Bundle\DataAuditBundle\Service\ConvertEntityToArrayForMessageQueueService;
use Oro\Bundle\DataAuditBundle\Service\GetEntityAuditMetadataService;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
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
     * @var ConvertEntityToArrayForMessageQueueService
     */
    private $convertEntityToArrayForMessageQueueService;

    /**
     * @var GetEntityAuditMetadataService
     */
    private $getEntityAuditMetadataService;

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
     * @param ConvertEntityToArrayForMessageQueueService $convertEntityToArrayForMessageQueueService
     * @param GetEntityAuditMetadataService $getEntityAuditMetadataService
     */
    public function __construct(
        MessageProducerInterface $messageProducer,
        TokenStorageInterface $securityTokenStorage,
        ConvertEntityToArrayForMessageQueueService $convertEntityToArrayForMessageQueueService,
        GetEntityAuditMetadataService $getEntityAuditMetadataService
    ) {
        $this->messageProducer = $messageProducer;
        $this->securityTokenStorage = $securityTokenStorage;
        $this->convertEntityToArrayForMessageQueueService = $convertEntityToArrayForMessageQueueService;
        $this->getEntityAuditMetadataService = $getEntityAuditMetadataService;

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

        if (! count($this->allInsertions[$em]) && ! count($this->allUpdates[$em]) &&
            ! count($this->allDeletions[$em]) && ! count($this->allCollectionUpdates[$em])) {
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

            $user = $this->getUser();
            if (null !== $user) {
                $body['user_id'] = $user->getId();
                $body['user_class'] = ClassUtils::getClass($user);
            }
            $organization = $this->getOrganization();
            if (null !== $organization) {
                $body['organization_id'] = $organization->getId();
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
            if (! $this->getEntityAuditMetadataService->getMetadata(get_class($entity))) {
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
            if (! $this->getEntityAuditMetadataService->getMetadata(get_class($entity))) {
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
            if (! $this->getEntityAuditMetadataService->getMetadata(get_class($entity))) {
                continue;
            }

            $changeSet = [];
            $entityMeta = $em->getClassMetadata(get_class($entity));

            // in order to audit many to one inverse side we have to store some info to change set.
            foreach ($entityMeta->associationMappings as $filedName => $mapping) {
                if (ClassMetadataInfo::MANY_TO_ONE == $mapping['type']) {
                    if ($relatedEntity = $entityMeta->getFieldValue($entity, $filedName)) {
                        $changeSet[$filedName] = [
                            $this->convertEntityToArrayForMessageQueueService
                                ->convertEntityToArray($em, $relatedEntity, []),
                            null
                        ];
                    }
                }
            }

            $deletions[$entity] = $this->convertEntityToArrayForMessageQueueService
                ->convertEntityToArray($em, $entity, $changeSet);
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
            if (! $this->getEntityAuditMetadataService->getMetadata($collection->getTypeClass()->getName())) {
                continue;
            }

            $insertDiff = $collection->getInsertDiff();
            $deleteDiff = [];
            foreach ($collection->getDeleteDiff() as $deletedEntity) {
                $deleteDiff[] = $this->convertEntityToArrayForMessageQueueService
                    ->convertEntityToArray($em, $deletedEntity, []);
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
        $insertions = [];
        foreach ($this->allInsertions[$em] as $entity) {
            $changeSet = $this->allInsertions[$em][$entity];
            $insertions[] = $this->convertEntityToArrayForMessageQueueService
                ->convertEntityToArray($em, $entity, $changeSet);
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
        $updates = [];
        foreach ($this->allUpdates[$em] as $entity) {
            $changeSet = $this->allUpdates[$em][$entity];
            $updates[] = $this->convertEntityToArrayForMessageQueueService
                ->convertEntityToArray($em, $entity, $changeSet);
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
        $collectionUpdates = [];

        foreach ($this->allCollectionUpdates[$em] as $collection) {
            /** @var PersistentCollection $collection */
            $new = ['inserted' => [], 'deleted' => [], 'changed' => [],];
            foreach ($this->allCollectionUpdates[$em][$collection]['insertDiff'] as $entity) {
                $new['inserted'][] = $this->convertEntityToArrayForMessageQueueService
                    ->convertEntityToArray($em, $entity, []);
            }
            foreach ($this->allCollectionUpdates[$em][$collection]['deleteDiff'] as $entity) {
                $new['deleted'][] = $entity;
            }

            $ownerFieldName = $collection->getMapping()['fieldName'];
            $entityData  = $this->convertEntityToArrayForMessageQueueService
                ->convertEntityToArray($em, $collection->getOwner(), []);
            $entityData['change_set'][$ownerFieldName] = [null, $new];

            if ($new['inserted'] || $new['deleted']) {
                $collectionUpdates[] = $entityData;
            }
        }

        return $collectionUpdates;
    }

    /**
     * @return AbstractUser|null
     */
    private function getUser()
    {
        $securityToken = $this->securityTokenStorage->getToken();
        if (null === $securityToken) {
            return null;
        }
        $user = $securityToken->getUser();
        if (!$user instanceof AbstractUser) {
            return null;
        }

        return $user;
    }

    /**
     * @return Organization|null
     */
    private function getOrganization()
    {
        $securityToken = $this->securityTokenStorage->getToken();
        if (null === $securityToken) {
            return null;
        }

        return $securityToken instanceof OrganizationContextTokenInterface
            ? $securityToken->getOrganizationContext()
            : null;
    }
}
