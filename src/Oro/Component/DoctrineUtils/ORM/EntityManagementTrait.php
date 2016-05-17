<?php

namespace Oro\Component\DoctrineUtils\ORM;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityRepository;

trait EntityManagementTrait
{
    /**
     * @var EntityRepository
     */
    private $repository;

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
     * @return EntityRepository
     */
    protected function getRepository()
    {
        if (null !== $this->repository) {
            return $this->repository;
        }

        return $this->repository = $this->getObjectManager()->getRepository($this->getEntityClass());
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
