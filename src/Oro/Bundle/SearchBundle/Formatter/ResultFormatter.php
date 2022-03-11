<?php

namespace Oro\Bundle\SearchBundle\Formatter;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\SearchBundle\Query\Result\Item;

/**
 * Group result entities by entity name with keeping list of actual entities in the same order
 */
class ResultFormatter
{
    protected DoctrineHelper $doctrineHelper;

    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * Get array of actual entities
     *
     * Result format: array(
     *     "entityName" => array(
     *         1 => Entity,
     *         2 => Entity,
     *         ...
     *     ),
     *     ...
     * )
     *
     * @param Item[] $elements
     * @return array
     */
    public function getResultEntities(array $elements)
    {
        $entities = array();

        // group elements by type
        foreach ($elements as $element) {
            $entityName = $element->getEntityName();
            $entities[$entityName][] = $element->getRecordId();
        }

        // get actual entities
        foreach ($entities as $entityName => $entityIds) {
            $entities[$entityName] = $this->getEntities($entityName, $entityIds);
        }

        return $entities;
    }

    /**
     * Get list of actual entities in the same order
     *
     * @param Item[] $elements
     * @return array
     */
    public function getOrderedResultEntities(array $elements)
    {
        $entities = $this->getResultEntities($elements);

        // replace elements with entities
        foreach ($elements as $key => $element) {
            $entityName = $element->getEntityName();
            $entityId   = $element->getRecordId();
            if (isset($entities[$entityName][$entityId])) {
                $elements[$key] = $entities[$entityName][$entityId];
            }
        }

        return $elements;
    }

    /**
     * @param string $entityName
     * @param array $entityIds
     * @return array
     */
    protected function getEntities($entityName, array $entityIds)
    {
        $classMetadata = $this->doctrineHelper->getEntityMetadataForClass($entityName);
        $idField = $this->doctrineHelper->getSingleEntityIdentifierFieldName($entityName);

        $queryBuilder = $this->doctrineHelper->getEntityRepository($entityName)->createQueryBuilder('e');
        $queryBuilder->where('e.' . $idField . ' IN (:entityIds)');
        $queryBuilder->setParameter('entityIds', $entityIds);
        $currentEntities = $queryBuilder->getQuery()->getResult();

        $resultEntities = array();
        foreach ($currentEntities as $entity) {
            $idValues = $classMetadata->getIdentifierValues($entity);
            $idValue = $idValues[$idField];
            $resultEntities[$idValue] = $entity;
        }

        return $resultEntities;
    }
}
