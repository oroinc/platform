<?php

namespace Oro\Bundle\ActivityListBundle\EventListener;

use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\EntityManager;

use Doctrine\ORM\UnitOfWork;
use Oro\Bundle\ActivityListBundle\Manager\ActivityListManager;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Doctrine\ORM\PersistentCollection;

class ActivityListListener
{
    /**  @var array */
    protected $insertedEntities = [];

    /**  @var array */
    protected $updatedEntities = [];

    /**  @var array */
    protected $deletedEntities = [];

    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var ActivityListManager
     */
    protected $activityListManager;

    /**
     * @param ActivityListManager $activityListManager
     */
    public function __construct(ActivityListManager $activityListManager, DoctrineHelper $doctrineHelper)
    {
        $this->activityListManager = $activityListManager;
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * Collect activities changes
     *
     * @param OnFlushEventArgs $args
     */
    public function onFlush(OnFlushEventArgs $args)
    {
        $entityManager = $args->getEntityManager();
        $unitOfWork = $entityManager->getUnitOfWork();
        $entityManager->getEventManager()->removeEventListener('onFlush', $this);

        $this->collectEntities($this->insertedEntities, $unitOfWork->getScheduledEntityInsertions());
        $this->collectUpdates($unitOfWork);
        $this->collectDeletedEntities($unitOfWork->getScheduledEntityDeletions());
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
        $entityManager->getEventManager()->removeEventListener('postFlush', $this);

        $this->processInsertEntities($entityManager);
        $this->processUpdatedEntities($entityManager);
        $this->processDeletedEntities($entityManager);

        $entityManager->getEventManager()->addEventListener('onFlush', $this);
        $entityManager->getEventManager()->addEventListener('postFlush', $this);
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
                if ($this->activityListManager->isSupportedEntity($entity) && empty($this->deletedEntities[$hash])) {
                    $this->deletedEntities[$hash] = [
                        'class' => $this->doctrineHelper->getEntityClass($entity),
                        'id'    => $this->doctrineHelper->getSingleEntityIdentifier($entity)
                    ];
                }
            }
        }
    }

    /**
     * Delete activity lists on delete activities
     *
     * @param EntityManager $entityManager
     */
    protected function processDeletedEntities(EntityManager $entityManager)
    {
        $this->activityListManager->processDeletedEntities($this->deletedEntities, $entityManager);
        $this->deletedEntities = [];
    }

    /**
     * Update Activity lists
     *
     * @param EntityManager $entityManager
     */
    protected function processUpdatedEntities(EntityManager $entityManager)
    {
        if ($this->activityListManager->processUpdatedEntities($this->updatedEntities, $entityManager)) {
            $this->updatedEntities = [];
            $entityManager->flush();
        }
    }

    /**
     * Process new records.
     *
     * @param EntityManager $entityManager
     */
    protected function processInsertEntities(EntityManager $entityManager)
    {
        if ($this->activityListManager->processInsertEntities($this->insertedEntities, $entityManager)) {
            $this->insertedEntities = [];
            $entityManager->flush();
        }
    }

    protected function collectUpdates(UnitOfWork $uof)
    {
        $this->updatedEntities['updates'] = [];
        $this->updatedEntities['collection_updates'] = [];
        $this->updatedEntities['collection_deletes'] = [];
        $entities = $uof->getScheduledEntityUpdates();
        foreach ($entities as $hash => $entity) {
            if ($this->activityListManager->isSupportedEntity($entity) && empty($this->updatedEntities['updates'][$hash])) {
                $this->updatedEntities['updates'][$hash] = [
                    $entity
                ];
            }
        }
        $updatedCollections = $uof->getScheduledCollectionUpdates();
        $deletedCollections = $uof->getScheduledCollectionDeletions();
        foreach ($updatedCollections as $collection) {
            /** @var $collection PersistentCollection */
            if ($this->activityListManager->isSupportedEntity($collection->getOwner())) {
                $this->updatedEntities['collection_updates'][] = $collection;
            }
        }
        foreach ($deletedCollections as $collection) {
            /** @var $collection PersistentCollection */
            if ($this->activityListManager->isSupportedEntity($collection->getOwner())) {
                $this->updatedEntities['collection_deletes'][] = $collection;
            }
        }
    }

    /**
     * Collect inserted or updated activities
     *
     * @param array $storage
     * @param array $entities
     */
    protected function collectEntities(array &$storage, array $entities)
    {
        foreach ($entities as $hash => $entity) {
            if ($this->activityListManager->isSupportedEntity($entity) && empty($storage[$hash])) {
                $storage[$hash] = $entity;
            }
        }
    }
}
