<?php

namespace Oro\Bundle\ImportExportBundle\Field;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManager;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

class DatabaseHelper
{
    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var array
     */
    protected $entities = [];

    /**
     * @param ManagerRegistry $registry
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(ManagerRegistry $registry, DoctrineHelper $doctrineHelper)
    {
        $this->registry = $registry;
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * @param string $entityName
     * @param array $criteria
     * @return object|null
     */
    public function findOneBy($entityName, array $criteria)
    {
        $serializationCriteria = array();
        $where = array();

        foreach ($criteria as $field => $value) {
            if (is_object($value)) {
                $serializationCriteria[$field] = $this->getIdentifier($value);
            } else {
                $serializationCriteria[$field] = $value;
            }
            $where[] = sprintf('e.%s = :%s', $field, $field);
        }

        $storageKey = serialize($serializationCriteria);

        if (empty($this->entities[$entityName]) || !array_key_exists($storageKey, $this->entities[$entityName])) {
            /** @var EntityRepository $entityRepository */
            $entityRepository = $this->registry->getRepository($entityName);
            $queryBuilder = $entityRepository->createQueryBuilder('e')
                ->andWhere(implode(' AND ', $where))
                ->setParameters($criteria)
                ->setMaxResults(1);

            $this->entities[$entityName][$storageKey] = $queryBuilder->getQuery()->getOneOrNullResult();
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
        return $this->doctrineHelper->getEntity($entityName, $identifier);
    }

    /**
     * @param object $entity
     * @return int|string|null
     */
    public function getIdentifier($entity)
    {
        return $this->doctrineHelper->getSingleEntityIdentifier($entity);
    }

    /**
     * @param string $entityName
     * @return string
     */
    public function getIdentifierFieldName($entityName)
    {
        return $this->doctrineHelper->getSingleEntityIdentifierFieldName($entityName);
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
     * @param string $entityName
     * @param string $fieldName
     * @return bool
     */
    public function getInversedRelationFieldName($entityName, $fieldName)
    {
        /** @var EntityManager $entityManager */
        $entityManager = $this->registry->getManagerForClass($entityName);
        $association = $entityManager->getClassMetadata($entityName)->getAssociationMapping($fieldName);

        if (!empty($association['mappedBy'])) {
            return $association['mappedBy'];
        }

        if (!empty($association['inversedBy'])) {
            return $association['inversedBy'];
        }

        return null;
    }

    /**
     * @param string $entityName
     * @param string $fieldName
     * @return bool
     */
    public function isSingleInversedRelation($entityName, $fieldName)
    {
        /** @var EntityManager $entityManager */
        $entityManager = $this->registry->getManagerForClass($entityName);
        $association = $entityManager->getClassMetadata($entityName)->getAssociationMapping($fieldName);

        return in_array($association['type'], array(ClassMetadata::ONE_TO_ONE, ClassMetadata::ONE_TO_MANY));
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
