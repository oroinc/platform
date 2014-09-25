<?php

namespace Oro\Bundle\ImportExportBundle\Field;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManager;

class DatabaseHelper
{
    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var array
     */
    protected $entities = [];

    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * @param string $entityName
     * @param array $criteria
     * @return object|null
     */
    public function findOneBy($entityName, array $criteria)
    {
        $storageKey = serialize($criteria);
        if (empty($this->entities[$entityName]) || !array_key_exists($storageKey, $this->entities[$entityName])) {
            $this->entities[$entityName][$storageKey]
                = $this->registry->getRepository($entityName)->findOneBy($criteria);
        }

        return $this->entities[$entityName][$storageKey];
    }

    /**
     * @param string $entityName
     * @param int|string $identifier
     * @return object|null
     */
    public function find($entityName, $identifier)
    {
        return $this->registry->getRepository($entityName)->find($identifier);
    }

    /**
     * @param object $entity
     * @return int|string|null
     */
    public function getIdentifier($entity)
    {
        $entityName = ClassUtils::getClass($entity);
        /** @var EntityManager $entityManager */
        $entityManager = $this->registry->getManagerForClass($entityName);
        $identifier = $entityManager->getClassMetadata($entityName)->getIdentifierValues($entity);
        return current($identifier);
    }

    /**
     * @param string $entityName
     * @return string
     */
    public function getIdentifierFieldName($entityName)
    {
        /** @var EntityManager $entityManager */
        $entityManager = $this->registry->getManagerForClass($entityName);
        return $entityManager->getClassMetadata($entityName)->getSingleIdentifierFieldName();
    }

    /**
     * @param string $entityName
     * @param string $fieldName
     * @return bool
     */
    public function isCascadePersist($entityName, $fieldName)
    {
        /** @var EntityManager $entityManager */
        $entityManager = $this->registry->getManagerForClass($entityName);
        $association = $entityManager->getClassMetadata($entityName)->getAssociationMapping($fieldName);
        return !empty($association['cascade']) && in_array('persist', $association['cascade']);
    }

    /**
     * @param object $entity
     */
    public function resetIdentifier($entity)
    {
        $entityName = ClassUtils::getClass($entity);
        /** @var EntityManager $entityManager */
        $entityManager = $this->registry->getManagerForClass($entityName);
        $identifierField = $this->getIdentifierFieldName($entityName);
        $entityManager->getClassMetadata($entityName)->setIdentifierValues($entity, array($identifierField => null));
    }

    /**
     * Clear cache on doctrine entity manager clear
     */
    public function onClear()
    {
        $this->entities = [];
    }
}
