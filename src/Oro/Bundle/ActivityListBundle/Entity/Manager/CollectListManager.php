<?php

namespace Oro\Bundle\ActivityListBundle\Entity\Manager;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Oro\Bundle\ActivityListBundle\Entity\ActivityList;
use Oro\Bundle\ActivityListBundle\Model\ActivityListApplicableProviderInterface;
use Oro\Bundle\ActivityListBundle\Model\ActivityListProviderInterface;
use Oro\Bundle\ActivityListBundle\Provider\ActivityListChainProvider;

/**
 * Processes the changes that was done in activity entities
 * and apply them to the related activity list entities.
 */
class CollectListManager
{
    /** @var ActivityListChainProvider */
    protected $chainProvider;

    /** @var EntityManager|null */
    private $entityManager;

    public function __construct(ActivityListChainProvider $chainProvider)
    {
        $this->chainProvider = $chainProvider;
    }

    /**
     * Check if given entity supports by activity list providers
     *
     * @param $entity
     * @return bool
     */
    public function isSupportedEntity($entity)
    {
        return $this->chainProvider->isSupportedEntity($entity);
    }

    /**
     * Check if given owner entity supports by activity list providers
     *
     * @param $entity
     * @return bool
     */
    public function isSupportedOwnerEntity($entity)
    {
        return $this->chainProvider->isSupportedOwnerEntity($entity);
    }

    /**
     * @param array         $deletedEntities
     * @param EntityManager $entityManager
     */
    public function processDeletedEntities($deletedEntities, EntityManager $entityManager)
    {
        if (!empty($deletedEntities)) {
            foreach ($deletedEntities as $entity) {
                $entityManager->getRepository(ActivityList::ENTITY_NAME)
                    ->deleteActivityListsByRelatedActivityData($entity['class'], $entity['id']);
            }
        }
    }

    /**
     * @param array         $updatedEntities
     * @param EntityManager $entityManager
     * @return bool
     */
    public function processUpdatedEntities($updatedEntities, EntityManager $entityManager)
    {
        if (!empty($updatedEntities)) {
            $metaData = $entityManager->getClassMetadata(ActivityList::ENTITY_CLASS);
            foreach ($updatedEntities as $entity) {
                $activityList = $this->chainProvider->getUpdatedActivityList($entity, $entityManager);
                if ($activityList) {
                    $entityManager->persist($activityList);
                    $entityManager->getUnitOfWork()->computeChangeSet(
                        $metaData,
                        $activityList
                    );
                }
            }

            return true;
        }

        return false;
    }

    /**
     * @param array         $insertedEntities
     * @param EntityManager $entityManager
     *
     * @return bool
     */
    public function processInsertEntities($insertedEntities, EntityManager $entityManager)
    {
        if (!empty($insertedEntities)) {
            $this->entityManager = $entityManager;
            foreach ($insertedEntities as $entity) {
                $activityList = $this->chainProvider->getActivityListEntitiesByActivityEntity($entity);
                if ($activityList) {
                    $activityListProvider = $this->chainProvider->getProviderForEntity($entity);
                    $this->fillOwners($activityListProvider, $entity, $activityList);
                    if ($this->isActivityListApplicable($activityListProvider, $activityList)) {
                        $entityManager->persist($activityList);
                    }
                }
            }

            return true;
        }

        return false;
    }

    /**
     * Fill Activity list owners from activity entity
     *
     * @param array $entities
     * @param EntityManager $entityManager
     *
     * @return bool
     */
    public function processFillOwners($entities, EntityManager $entityManager)
    {
        if ($entities) {
            $this->entityManager = $entityManager;
            foreach ($entities as $entity) {
                $activityList = $this->chainProvider->getActivityListByEntity($entity, $entityManager);
                if ($activityList) {
                    $activityListProvider = $this->chainProvider->getProviderForOwnerEntity($entity);
                    $this->fillOwners($activityListProvider, $entity, $activityList);
                    if (!$this->isActivityListApplicable($activityListProvider, $activityList)) {
                        $entityManager->remove($activityList);
                    }
                }
            }

            return true;
        }

        return false;
    }

    private function isActivityListApplicable(
        ActivityListProviderInterface $provider,
        ActivityList $activityList
    ): bool {
        return $provider instanceof ActivityListApplicableProviderInterface
            ? $provider->isActivityListApplicable($activityList)
            : true;
    }

    /**
     * @param ActivityListProviderInterface $provider
     * @param object $entity
     * @param ActivityList $activityList
     */
    protected function fillOwners(
        ActivityListProviderInterface $provider,
        $entity,
        ActivityList $activityList
    ) {
        $oldActivityOwners = $activityList->getActivityOwners();
        $newActivityOwners = $provider->getActivityOwners($entity, $activityList);
        $newActivityOwners = new ArrayCollection($newActivityOwners);

        foreach ($oldActivityOwners as $oldOwner) {
            if (!$oldOwner->isOwnerInCollection($newActivityOwners)) {
                $activityList->removeActivityOwner($oldOwner);
                $this->entityManager->remove($oldOwner);
            }
        }

        foreach ($newActivityOwners as $newOwner) {
            if (!$newOwner->isOwnerInCollection($oldActivityOwners)) {
                $activityList->addActivityOwner($newOwner);
            }
        }
    }
}
