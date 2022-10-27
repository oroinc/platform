<?php

namespace Oro\Bundle\DataAuditBundle\Model;

use Doctrine\ORM\EntityManagerInterface;

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
