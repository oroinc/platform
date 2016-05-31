<?php

namespace Oro\Bundle\ApiBundle\Util;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\QueryBuilder;

use Oro\Component\DoctrineUtils\ORM\QueryUtils;
use Oro\Component\PhpUtils\ReflectionUtil;
use Oro\Bundle\ApiBundle\Collection\Criteria;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper as BaseHelper;

class DoctrineHelper extends BaseHelper
{
    /** @var array */
    private $manageableEntityClasses = [];

    /**
     * {@inheritdoc}
     */
    public function isManageableEntityClass($entityClass)
    {
        if (isset($this->manageableEntityClasses[$entityClass])) {
            return $this->manageableEntityClasses[$entityClass];
        }

        $isManageable = null !== $this->getEntityManagerForClass($entityClass, false);
        $this->manageableEntityClasses[$entityClass] = $isManageable;

        return $isManageable;
    }

    /**
     * Adds criteria to the query.
     *
     * @param QueryBuilder $qb
     * @param Criteria     $criteria
     */
    public function applyCriteria(QueryBuilder $qb, Criteria $criteria)
    {
        $joins = $criteria->getJoins();
        if (!empty($joins)) {
            $basePlaceholders = [];
            foreach ($joins as $path => $join) {
                $basePlaceholders[sprintf(Criteria::PLACEHOLDER_TEMPLATE, $path)] = $join->getAlias();
            }
            $basePlaceholders[Criteria::ROOT_ALIAS_PLACEHOLDER] = QueryUtils::getSingleRootAlias($qb);
            foreach ($joins as $join) {
                $alias        = $join->getAlias();
                $placeholders = array_merge($basePlaceholders, [Criteria::ENTITY_ALIAS_PLACEHOLDER => $alias]);
                $joinExpr     = strtr($join->getJoin(), $placeholders);
                $condition    = $join->getCondition();
                if ($condition) {
                    $condition = strtr($condition, $placeholders);
                }

                $method = strtolower($join->getJoinType()) . 'Join';
                $qb->{$method}($joinExpr, $alias, $join->getConditionType(), $condition, $join->getIndexBy());
            }
        }
        $qb->addCriteria($criteria);
    }

    /**
     * Gets the ORM metadata descriptor for target entity class of the given child association.
     *
     * @param string   $entityClass
     * @param string[] $associationPath
     *
     * @return ClassMetadata|null
     */
    public function findEntityMetadataByPath($entityClass, array $associationPath)
    {
        $metadata = $this->getEntityMetadataForClass($entityClass, false);
        if (null !== $metadata) {
            foreach ($associationPath as $associationName) {
                if (!$metadata->hasAssociation($associationName)) {
                    $metadata = null;
                    break;
                }
                $metadata = $this->getEntityMetadataForClass($metadata->getAssociationTargetClass($associationName));
            }
        }

        return $metadata;
    }

    /**
     * Gets ORDER BY expression that can be used to sort a collection by entity identifier.
     *
     * @param string $entityClass
     * @param bool   $desc
     *
     * @return array|null
     */
    public function getOrderByIdentifier($entityClass, $desc = false)
    {
        $idFieldNames = $this->getEntityIdentifierFieldNamesForClass($entityClass);
        if (empty($idFieldNames)) {
            return null;
        }

        $orderBy = [];
        $order   = $desc ? Criteria::DESC : Criteria::ASC;
        foreach ($idFieldNames as $idFieldName) {
            $orderBy[$idFieldName] = $order;
        }

        return $orderBy;
    }

    /**
     * Gets a list of all indexed fields
     *
     * @param ClassMetadata $metadata
     *
     * @return array [field name => field data-type, ...]
     */
    public function getIndexedFields(ClassMetadata $metadata)
    {
        $indexedColumns = [];

        $idFieldNames = $metadata->getIdentifierFieldNames();
        if (count($idFieldNames) > 0) {
            $mapping = $metadata->getFieldMapping(reset($idFieldNames));

            $indexedColumns[$mapping['columnName']] = true;
        }

        if (isset($metadata->table['indexes'])) {
            foreach ($metadata->table['indexes'] as $index) {
                $firstFieldName = reset($index['columns']);
                if (!isset($indexedColumns[$firstFieldName])) {
                    $indexedColumns[$firstFieldName] = true;
                }
            }
        }

        $fields     = [];
        $fieldNames = $metadata->getFieldNames();
        foreach ($fieldNames as $fieldName) {
            $mapping  = $metadata->getFieldMapping($fieldName);
            $hasIndex = false;
            if (isset($mapping['unique']) && true === $mapping['unique']) {
                $hasIndex = true;
            } elseif (array_key_exists($mapping['columnName'], $indexedColumns)) {
                $hasIndex = true;
            }
            if ($hasIndex) {
                $fields[$fieldName] = $mapping['type'];
            }
        }

        return $fields;
    }

    /**
     * Gets a list of all indexed associations
     *
     * @param ClassMetadata $metadata
     *
     * @return array [field name => target field data-type, ...]
     */
    public function getIndexedAssociations(ClassMetadata $metadata)
    {
        $relations  = [];
        $fieldNames = $metadata->getAssociationNames();
        foreach ($fieldNames as $fieldName) {
            $mapping = $metadata->getAssociationMapping($fieldName);
            if ($mapping['type'] & ClassMetadata::TO_ONE) {
                $targetMetadata     = $this->getEntityMetadataForClass($mapping['targetEntity']);
                $targetIdFieldNames = $targetMetadata->getIdentifierFieldNames();
                if (count($targetIdFieldNames) === 1) {
                    $relations[$fieldName] = $targetMetadata->getTypeOfField(reset($targetIdFieldNames));
                }
            }
        }

        return $relations;
    }

    /**
     * Sets the identifier values for a given entity.
     *
     * @param object             $entity
     * @param mixed              $entityId
     * @param ClassMetadata|null $metadata
     *
     * @throws \InvalidArgumentException
     */
    public function setEntityIdentifier($entity, $entityId, ClassMetadata $metadata = null)
    {
        if (null === $metadata) {
            $metadata = $this->getEntityMetadata($entity);
        }

        if (!is_array($entityId)) {
            $idFieldNames = $metadata->getIdentifierFieldNames();
            if (count($idFieldNames) > 1) {
                throw new \InvalidArgumentException(
                    sprintf(
                        'Unexpected identifier value "%s" for composite primary key of the entity "%s".',
                        $entityId,
                        $metadata->getName()
                    )
                );
            }
            $entityId = [reset($idFieldNames) => $entityId];
        }

        $reflClass = new \ReflectionClass($entity);
        foreach ($entityId as $fieldName => $value) {
            $property = ReflectionUtil::getProperty($reflClass, $fieldName);
            if (null === $property) {
                throw new \InvalidArgumentException(
                    sprintf(
                        'The entity "%s" does not have the "%s" property.',
                        get_class($entity),
                        $fieldName
                    )
                );
            }

            if (!$property->isPublic()) {
                $property->setAccessible(true);
            }
            $property->setValue($entity, $value);
        }
    }
}
