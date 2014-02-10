<?php

namespace Oro\Bundle\EntityBundle\ORM;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManager;

use Oro\Bundle\EntityBundle\Exception;

class DoctrineHelper
{
    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * @param object $entity
     * @return string
     */
    public function getEntityClass($entity)
    {
        return ClassUtils::getClass($entity);
    }

    /**
     * @param object $entity
     * @return array
     */
    public function getEntityIdentifier($entity)
    {
        $entityManager = $this->getEntityManager($entity);
        $metadata = $entityManager->getClassMetadata(ClassUtils::getClass($entity));
        $identifier = $metadata->getIdentifierValues($entity);

        return $identifier;
    }

    /**
     * @param object $entity
     * @param bool $triggerException
     * @return integer|null
     * @throws Exception\InvalidEntityException
     */
    public function getSingleEntityIdentifier($entity, $triggerException = true)
    {
        $entityIdentifier = $this->getEntityIdentifier($entity);

        $result = null;
        if (count($entityIdentifier) != 1) {
            if ($triggerException) {
                throw new Exception\InvalidEntityException('Can\'t get single identifier for the entity');
            }
        } else {
            $result = current($entityIdentifier);
        }

        return $result;
    }

    /**
     * @param object $entity
     * @return bool
     */
    public function isManageableEntity($entity)
    {
        $entityClass = $this->getEntityClass($entity);
        $entityManager = $this->registry->getManagerForClass($entityClass);

        return !empty($entityManager);
    }

    /**
     * @param string $entityOrClass
     * @return EntityManager
     * @throws Exception\NotManageableEntityException
     */
    public function getEntityManager($entityOrClass)
    {
        if (is_object($entityOrClass)) {
            $entityClass = $this->getEntityClass($entityOrClass);
        } else {
            $entityClass = $entityOrClass;
        }

        $entityManager = $this->registry->getManagerForClass($entityClass);
        if (!$entityManager) {
            throw new Exception\NotManageableEntityException($entityClass);
        }

        return $entityManager;
    }

    /**
     * @param string $entityClass
     * @param mixed $entityId
     * @return mixed
     */
    public function getEntityReference($entityClass, $entityId)
    {
        $entityManager = $this->getEntityManager($entityClass);
        return $entityManager->getReference($entityClass, $entityId);
    }
}
