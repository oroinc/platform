<?php

namespace Oro\Bundle\NotificationBundle\Doctrine;

use Doctrine\ORM\EntityManagerInterface;

/**
 * The storage of entities that is used to manage batch persist and flush operations.
 */
class EntityPool
{
    /** @var array */
    private $persistEntities = [];

    /**
     * Adds an entity to the persist list.
     *
     * @param object $entity
     */
    public function addPersistEntity($entity)
    {
        $this->persistEntities[] = $entity;
    }

    /**
     * Persists entities and clear this pool
     *
     * @param EntityManagerInterface $entityManager
     *
     * @return bool TRUE if there were entities to persist; otherwise returns FALSE.
     */
    public function persistAndClear(EntityManagerInterface $entityManager)
    {
        if (!$this->persistEntities) {
            return false;
        }

        foreach ($this->persistEntities as $entity) {
            $entityManager->persist($entity);
        }

        $this->persistEntities = [];

        return true;
    }

    /**
     * Persists and flush entities.
     *
     * @param EntityManagerInterface $entityManager
     *
     * @return bool TRUE if there were entities to persist and they were flushed; otherwise returns FALSE.
     */
    public function persistAndFlush(EntityManagerInterface $entityManager)
    {
        $flushed = false;
        if ($this->persistAndClear($entityManager)) {
            $entityManager->flush();
            $flushed = true;
        }

        return $flushed;
    }
}
