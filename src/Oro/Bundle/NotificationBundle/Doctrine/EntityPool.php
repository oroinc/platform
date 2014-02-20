<?php

namespace Oro\Bundle\NotificationBundle\Doctrine;

use Doctrine\ORM\EntityManager;

class EntityPool
{
    /**
     * @var array
     */
    protected $persistEntities = array();

    /**
     * Adds entity to persist.
     *
     * @param object $entity
     */
    public function addPersistEntity($entity)
    {
        $this->persistEntities[] = $entity;
    }

    /**
     * Persist entities to entity manager. If there were entities to persist returns TRUE, otherwise returns FALSE.
     *
     * @param EntityManager $entityManager
     * @return bool
     */
    public function persistAndClear(EntityManager $entityManager)
    {
        if (!$this->persistEntities) {
            return false;
        }

        foreach ($this->persistEntities as $entity) {
            $entityManager->persist($entity);
        }

        $this->persistEntities = array();

        return true;
    }

    /**
     * Persist entities. If has entities to persist then call flush them and returns TRUE, otherwise returns FALSE.
     *
     * @param EntityManager $entityManager
     * @return bool
     */
    public function persistAndFlush(EntityManager $entityManager)
    {
        if ($this->persistAndClear($entityManager)) {
            $entityManager->flush();

            return true;
        }

        return false;
    }
}
