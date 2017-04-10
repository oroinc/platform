<?php

namespace Oro\Bundle\ActivityListBundle\EventListener;

use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
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

        $this->collectInsertedEntities($unitOfWork->getScheduledEntityInsertions());
        $this->collectUpdatedEntities($unitOfWork->getScheduledEntityUpdates());
        $this->collectUpdatedCollections($unitOfWork->getScheduledCollectionUpdates());
        $this->collectUpdatedCollections($unitOfWork->getScheduledCollectionDeletions());
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
        $entityManager = $args->getEntityManager();

        $this->activityListManager->processDeletedEntities($this->deletedEntities, $entityManager);
        $this->deletedEntities = [];

        $hasChanges = false;
        if ($this->activityListManager->processInsertEntities($this->insertedEntities, $entityManager)) {
            $this->insertedEntities = [];
            $hasChanges = true;
        }
        if ($this->activityListManager->processFillOwners($this->insertedOwnerEntities, $entityManager)) {
            $this->insertedOwnerEntities = [];
            $hasChanges = true;
        }
        if ($hasChanges) {
            $entityManager->flush();
            $entityManager->clear('Oro\Bundle\ActivityListBundle\Entity\ActivityList');
            $entityManager->clear('Oro\Bundle\ActivityListBundle\Entity\ActivityOwner');
        }
    }

    /**
     * We should collect here id's because after flush, object has no id
     *
     * @param object[] $entities
     */
    protected function collectDeletedEntities(array $entities)
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
     * Collect updated activities amd activity owners
     *
     * @param object[] $entities
     */
    protected function collectUpdatedEntities(array $entities)
    {
        foreach ($entities as $hash => $entity) {
            if (empty($this->updatedOwnerEntities[$hash])
                && $this->activityListManager->isSupportedOwnerEntity($entity)) {
                $this->updatedOwnerEntities[$hash] = $entity;
            }
            if (empty($this->updatedEntities[$hash])
                && $this->activityListManager->isSupportedEntity($entity)
            ) {
                $this->updatedEntities[$hash] = $entity;
            }
        }
    }

    /**
     * Collect updated activities owner entities
     *
     * @param PersistentCollection[] $collections
     */
    protected function collectUpdatedCollections(array $collections)
    {
        foreach ($collections as $hash => $collection) {
            $ownerEntity = $collection->getOwner();
            if (null === $this->doctrineHelper->getSingleEntityIdentifier($ownerEntity)) {
                continue;
            }

            $entityHash  = spl_object_hash($ownerEntity);
            if (empty($this->updatedOwnerEntities[$entityHash])
                && $this->activityListManager->isSupportedOwnerEntity($ownerEntity)
            ) {
                $this->updatedOwnerEntities[$entityHash] = $ownerEntity;
            }
            if (empty($this->updatedEntities[$entityHash])
                && $this->activityListManager->isSupportedEntity($ownerEntity)
            ) {
                $this->updatedEntities[$entityHash] = $ownerEntity;
            }
        }
    }

    /**
     * Collect inserted activities and activity owners
     *
     * @param array $entities
     */
    protected function collectInsertedEntities(array $entities)
    {
        foreach ($entities as $hash => $entity) {
            if (empty($this->insertedOwnerEntities[$hash])
                && $this->activityListManager->isSupportedOwnerEntity($entity)
            ) {
                $this->insertedOwnerEntities[$hash] = $entity;
            }
            if (empty($this->insertedEntities[$hash])
                && $this->activityListManager->isSupportedEntity($entity)
            ) {
                $this->insertedEntities[$hash] = $entity;
            }
        }
    }
}
