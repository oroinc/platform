<?php

namespace Oro\Bundle\ActivityListBundle\EventListener;

use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\UnitOfWork;
use Doctrine\ORM\PersistentCollection;

use Oro\Bundle\ActivityListBundle\Entity\Manager\CollectListManager;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

class ActivityListListener
{
    /** @var array */
    protected $insertedOwnerEntities = [];

    /** @var array */
    protected $insertedEntities = [];

    /** @var array */
    protected $updatedEntities = [];

    /** @var array */
    protected $updatedOwnerEntities = [];

    /** @var array */
    protected $deletedEntities = [];

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var CollectListManager */
    protected $activityListManager;

    /**
     * @param CollectListManager $activityListManager
     * @param DoctrineHelper     $doctrineHelper
     */
    public function __construct(
        CollectListManager $activityListManager,
        DoctrineHelper $doctrineHelper
    ) {
        $this->activityListManager = $activityListManager;
        $this->doctrineHelper      = $doctrineHelper;
    }

    /**
     * Collect activities changes
     *
     * @param OnFlushEventArgs $args
     */
    public function onFlush(OnFlushEventArgs $args)
    {
        $entityManager = $args->getEntityManager();
        $unitOfWork    = $entityManager->getUnitOfWork();

        $this->collectInsertedOwnerEntities($unitOfWork->getScheduledEntityInsertions());
        $this->collectInsertedEntities($unitOfWork->getScheduledEntityInsertions());
        $this->collectUpdatesOwnerEntities($unitOfWork);
        $this->collectUpdates($unitOfWork);
        $this->collectDeletedEntities($unitOfWork->getScheduledEntityDeletions());

        if ($this->activityListManager->processUpdatedEntities($this->updatedEntities, $entityManager)) {
            $this->updatedEntities = [];
        }

        if ($this->activityListManager->processFillOwners($this->updatedOwnerEntities, $entityManager)) {
            $this->updatedOwnerEntities = [];
        }
    }

    /**
     * Save collected changes
     *
     * @param PostFlushEventArgs $args
     */
    public function postFlush(PostFlushEventArgs $args)
    {
        /** @var $entityManager */
        $entityManager = $args->getEntityManager();

        $this->activityListManager->processDeletedEntities($this->deletedEntities, $entityManager);
        $this->deletedEntities = [];

        if ($this->activityListManager->processInsertEntities($this->insertedEntities, $entityManager)) {
            $this->insertedEntities = [];
            $entityManager->flush();
        }
        if ($this->activityListManager->processFillOwners($this->insertedOwnerEntities, $entityManager)) {
            $this->insertedOwnerEntities = [];
            $entityManager->flush();
        }
    }

    /**
     * We should collect here id's because after flush, object has no id
     *
     * @param $entities
     */
    protected function collectDeletedEntities($entities)
    {
        if (!empty($entities)) {
            foreach ($entities as $hash => $entity) {
                if (empty($this->deletedEntities[$hash])
                    && $this->activityListManager->isSupportedEntity($entity)
                ) {
                    $this->deletedEntities[$hash] = [
                        'class' => $this->doctrineHelper->getEntityClass($entity),
                        'id'    => $this->doctrineHelper->getSingleEntityIdentifier($entity)
                    ];
                }
            }
        }
    }

    /**
     * Collect updated activities
     *
     * @param UnitOfWork $uof
     */
    protected function collectUpdates(UnitOfWork $uof)
    {
        $entities = $uof->getScheduledEntityUpdates();
        foreach ($entities as $hash => $entity) {
            if (empty($this->updatedEntities[$hash])
                && $this->activityListManager->isSupportedEntity($entity)
            ) {
                $this->updatedEntities[$hash] = $entity;
            }
        }
        $updatedCollections = array_merge(
            $uof->getScheduledCollectionUpdates(),
            $uof->getScheduledCollectionDeletions()
        );
        foreach ($updatedCollections as $hash => $collection) {
            /** @var $collection PersistentCollection */
            $ownerEntity = $collection->getOwner();
            $entityHash  = spl_object_hash($ownerEntity);
            if (empty($this->updatedEntities[$entityHash])
                && $this->activityListManager->isSupportedEntity($ownerEntity)
                && $this->doctrineHelper->getSingleEntityIdentifier($ownerEntity) !== null
            ) {
                $this->updatedEntities[$entityHash] = $ownerEntity;
            }
        }
    }

    /**
     * Collect updated activities owner entities
     *
     * @param UnitOfWork $uof
     */
    protected function collectUpdatesOwnerEntities(UnitOfWork $uof)
    {
        $entities = $uof->getScheduledEntityUpdates();
        foreach ($entities as $hash => $entity) {
            if ($this->activityListManager->isSupportedOwnerEntity($entity)
                && empty($this->updatedOwnerEntities[$hash])) {
                $this->updatedOwnerEntities[$hash] = $entity;
            }
        }
        $updatedCollections = array_merge(
            $uof->getScheduledCollectionUpdates(),
            $uof->getScheduledCollectionDeletions()
        );
        foreach ($updatedCollections as $hash => $collection) {
            /** @var $collection PersistentCollection */
            $ownerEntity = $collection->getOwner();
            $entityHash  = spl_object_hash($ownerEntity);
            if ($this->activityListManager->isSupportedOwnerEntity($ownerEntity)
                && $this->doctrineHelper->getSingleEntityIdentifier($ownerEntity) !== null
                && empty($this->updatedOwnerEntities[$entityHash])
            ) {
                $this->updatedOwnerEntities[$entityHash] = $ownerEntity;
            }
        }
    }

    /**
     * Collect inserted activities
     *
     * @param array $entities
     */
    protected function collectInsertedEntities(array $entities)
    {
        foreach ($entities as $hash => $entity) {
            if (empty($this->insertedEntities[$hash])
                && $this->activityListManager->isSupportedEntity($entity)
            ) {
                $this->insertedEntities[$hash] = $entity;
            }
        }
    }

    /**
     * Collect inserted owners activity entities
     *
     * @param array $entities
     */
    protected function collectInsertedOwnerEntities(array $entities)
    {
        foreach ($entities as $hash => $entity) {
            if ($this->activityListManager->isSupportedOwnerEntity($entity)
                && empty($this->insertedOwnerEntities[$hash])) {
                $this->insertedOwnerEntities[$hash] = $entity;
            }
        }
    }
}
