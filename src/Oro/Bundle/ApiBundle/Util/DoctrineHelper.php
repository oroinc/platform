<?php

namespace Oro\Bundle\ApiBundle\Util;

use Doctrine\ORM\Mapping\ClassMetadata;

use Oro\Bundle\ApiBundle\Collection\Criteria;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper as BaseHelper;

class DoctrineHelper extends BaseHelper
{
    /** @var array */
    protected $manageableEntityClasses = [];

    /**
     * {@inheritdoc}
     */
    public function isManageableEntityClass($entityClass)
    {
        if (isset($this->manageableEntityClasses[$entityClass])) {
            return $this->manageableEntityClasses[$entityClass];
        }

        $isManageable = null !== $this->registry->getManagerForClass($entityClass);
        $this->manageableEntityClasses[$entityClass] = $isManageable;

        return $isManageable;
    }

    /**
     * Gets the ORM metadata descriptor for target entity class of the given child association.
     *
     * @param string          $entityClass
     * @param string[]|string $associationPath
     *
     * @return ClassMetadata|null
     */
    public function findEntityMetadataByPath($entityClass, $associationPath)
    {
        $manager = $this->registry->getManagerForClass($entityClass);
        if (null === $manager) {
            return null;
        }

        $metadata = $manager->getClassMetadata($entityClass);
        if (null !== $metadata) {
            if (!is_array($associationPath)) {
                $associationPath = explode('.', $associationPath);
            }
            foreach ($associationPath as $associationName) {
                if (!$metadata->hasAssociation($associationName)) {
                    $metadata = null;
                    break;
                }
                $metadata = $manager->getClassMetadata($metadata->getAssociationTargetClass($associationName));
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
        $order = $desc ? Criteria::DESC : Criteria::ASC;
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

        $fields = [];
        $fieldNames = $metadata->getFieldNames();
        foreach ($fieldNames as $fieldName) {
            $mapping = $metadata->getFieldMapping($fieldName);
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
        $relations = [];
        $fieldNames = $metadata->getAssociationNames();
        foreach ($fieldNames as $fieldName) {
            $mapping = $metadata->getAssociationMapping($fieldName);
            if ($mapping['type'] & ClassMetadata::TO_ONE) {
                $targetMetadata = $this->getEntityMetadataForClass($mapping['targetEntity']);
                $targetIdFieldNames = $targetMetadata->getIdentifierFieldNames();
                if (count($targetIdFieldNames) === 1) {
                    $relations[$fieldName] = $targetMetadata->getTypeOfField(reset($targetIdFieldNames));
                }
            }
        }

        return $relations;
    }

    /**
     * Gets the data type of the specified field
     * or the data type of identifier field if the specified field is an association.
     *
     * @param ClassMetadata $metadata
     * @param string        $fieldName
     *
     * @return string|null The data type or NULL if the field does not exist
     */
    public function getFieldDataType(ClassMetadata $metadata, $fieldName)
    {
        $dataType = null;
        if ($metadata->hasField($fieldName)) {
            $dataType = $metadata->getTypeOfField($fieldName);
        } elseif ($metadata->hasAssociation($fieldName)) {
            $targetMetadata = $this->getEntityMetadataForClass(
                $metadata->getAssociationTargetClass($fieldName)
            );
            $targetIdFieldNames = $targetMetadata->getIdentifierFieldNames();
            if (count($targetIdFieldNames) === 1) {
                $dataType = $targetMetadata->getTypeOfField(reset($targetIdFieldNames));
            } else {
                $dataType = DataType::STRING;
            }
        }

        return $dataType;
    }
}
