<?php

namespace Oro\Bundle\DataAuditBundle\Model;

use Doctrine\ORM\EntityManagerInterface;

/**
 * Stores additional entity changes that should be included in audit logs.
 *
 * This storage service maintains a collection of entity updates that need to be audited but are not
 * automatically detected by Doctrine's change tracking (e.g., programmatically triggered changes,
 * computed field updates, or changes from external systems). It uses {@see \SplObjectStorage} to organize
 * changes by entity manager and entity instance, allowing the audit system to process these additional
 * changes alongside the standard Doctrine-tracked modifications during the flush operation.
 */
class AdditionalEntityChangesToAuditStorage
{
    /**
     * @var \SplObjectStorage
     */
    private $additionalUpdates;

    public function __construct()
    {
        $this->additionalUpdates = new \SplObjectStorage();
    }

    /**
     * @param EntityManagerInterface $entityManager
     * @param object $entity
     * @param array $changeSet
     */
    public function addEntityUpdate(EntityManagerInterface $entityManager, $entity, array $changeSet)
    {
        if (!$this->additionalUpdates->contains($entityManager)) {
            $updates = new \SplObjectStorage();
            $updates->offsetSet($entity, $changeSet);
            $this->additionalUpdates->offsetSet($entityManager, $updates);

            return;
        }

        /** @var \SplObjectStorage $existingUpdates */
        $existingUpdates = $this->additionalUpdates->offsetGet($entityManager);
        if ($existingUpdates->contains($entity)) {
            $existingChangeSets = $existingUpdates->offsetGet($entity);
            $existingUpdates->offsetSet($entity, array_merge($existingChangeSets, $changeSet));
        } else {
            $existingUpdates->offsetSet($entity, $changeSet);
        }
    }

    public function hasEntityUpdates(EntityManagerInterface $entityManager): bool
    {
        return $this->additionalUpdates->contains($entityManager);
    }

    public function getEntityUpdates(EntityManagerInterface $entityManager): \SplObjectStorage
    {
        if ($this->additionalUpdates->contains($entityManager)) {
            return $this->additionalUpdates->offsetGet($entityManager);
        }

        return new \SplObjectStorage();
    }

    public function clear(EntityManagerInterface $entityManager)
    {
        if ($this->additionalUpdates->contains($entityManager)) {
            $this->additionalUpdates->detach($entityManager);
        }
    }
}
