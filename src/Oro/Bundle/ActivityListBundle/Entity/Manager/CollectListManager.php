<?php

namespace Oro\Bundle\ActivityListBundle\Entity\Manager;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\ActivityListBundle\Entity\ActivityList;
use Oro\Bundle\ActivityListBundle\Model\ActivityListProviderInterface;
use Oro\Bundle\ActivityListBundle\Provider\ActivityListChainProvider;

/**
 * Processes the changes that was done in activity entities
 * and apply them to the related activity list entities.
 */
class CollectListManager
{
    private ActivityListChainProvider $chainProvider;

    public function __construct(ActivityListChainProvider $chainProvider)
    {
        $this->chainProvider = $chainProvider;
    }

    /**
     * Checks if the given entity is supported activity entity.
     */
    public function isSupportedEntity(object $entity): bool
    {
        return $this->chainProvider->isSupportedEntity($entity);
    }

    /**
     * Checks if given owner entity is supported activity owner entity.
     */
    public function isSupportedOwnerEntity(object $entity): bool
    {
        return $this->chainProvider->isSupportedOwnerEntity($entity);
    }

    public function processDeletedEntities(array $deletedEntities, EntityManagerInterface $entityManager): void
    {
        if (!$deletedEntities) {
            return;
        }

        foreach ($deletedEntities as $entity) {
            $entityManager->getRepository(ActivityList::class)
                ->deleteActivityListsByRelatedActivityData($entity['class'], $entity['id']);
        }
    }

    public function processUpdatedEntities(array $updatedEntities, EntityManagerInterface $entityManager): bool
    {
        if (!$updatedEntities) {
            return false;
        }

        $metaData = $entityManager->getClassMetadata(ActivityList::class);
        foreach ($updatedEntities as $entity) {
            $activityList = $this->chainProvider->getUpdatedActivityList($entity, $entityManager);
            if ($activityList) {
                $entityManager->persist($activityList);
                $entityManager->getUnitOfWork()->computeChangeSet($metaData, $activityList);
            }
        }

        return true;
    }

    public function processInsertEntities(array $insertedEntities, EntityManagerInterface $entityManager): bool
    {
        if (!$insertedEntities) {
            return false;
        }

        foreach ($insertedEntities as $entity) {
            $activityList = $this->chainProvider->getActivityListEntitiesByActivityEntity($entity);
            if ($activityList) {
                $activityListProvider = $this->chainProvider->getProviderForEntity($entity);
                $this->fillOwners($activityListProvider, $entity, $activityList, $entityManager);
                if ($activityListProvider->isActivityListApplicable($activityList)) {
                    $entityManager->persist($activityList);
                }
            }
        }

        return true;
    }

    /**
     * Fills activity list owners from activity entity.
     */
    public function processFillOwners(array $entities, EntityManagerInterface $entityManager): bool
    {
        if (!$entities) {
            return false;
        }

        foreach ($entities as $entity) {
            $activityList = $this->chainProvider->getActivityListByEntity($entity, $entityManager);
            if ($activityList) {
                $activityListProvider = $this->chainProvider->getProviderForOwnerEntity($entity);
                $this->fillOwners($activityListProvider, $entity, $activityList, $entityManager);
                if (!$activityListProvider->isActivityListApplicable($activityList)) {
                    $entityManager->remove($activityList);
                }
            }
        }

        return true;
    }

    private function fillOwners(
        ActivityListProviderInterface $provider,
        object $entity,
        ActivityList $activityList,
        EntityManagerInterface $entityManager
    ): void {
        $oldActivityOwners = $activityList->getActivityOwners();
        $newActivityOwners = $provider->getActivityOwners($entity, $activityList);
        $newActivityOwners = new ArrayCollection($newActivityOwners);

        foreach ($oldActivityOwners as $oldOwner) {
            if (!$oldOwner->isOwnerInCollection($newActivityOwners)) {
                $activityList->removeActivityOwner($oldOwner);
                $entityManager->remove($oldOwner);
            }
        }

        foreach ($newActivityOwners as $newOwner) {
            if (!$newOwner->isOwnerInCollection($oldActivityOwners)) {
                $activityList->addActivityOwner($newOwner);
            }
        }
    }
}
