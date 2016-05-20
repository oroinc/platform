<?php

namespace Oro\Component\DoctrineUtils\ORM;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;

/**
 * Trait EntityManagerResolvingTrait is helpful when class logic is coupled around single entity
 * with multiple ObjectManagers in system.
 * It defines contract to ManagerRegistry to be injected and entity name to be aware of.
 */
trait EntityManagerResolvingTrait
{
    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @return ObjectManager
     */
    protected function getObjectManager()
    {
        if (null !== $this->objectManager) {
            return $this->objectManager;
        }

        return $this->objectManager = $this->getManagerRegistry()->getManagerForClass($this->getEntityClass());
    }

    /**
     * @return string
     */
    abstract protected function getEntityClass();

    /**
     * @return ManagerRegistry
     */
    abstract protected function getManagerRegistry();
}
