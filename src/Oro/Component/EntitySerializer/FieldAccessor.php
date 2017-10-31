<?php

namespace Oro\Component\EntitySerializer;

use Doctrine\Common\Util\ClassUtils;

class FieldAccessor
{
    /** @internal Uses for caching the built list of fields */
    const FIELDS_ALL = '_fields';
    /** @internal Uses for caching the built list of fields to be selected */
    const FIELDS_SELECT = '_select';
    /** @internal Uses for caching the built lists of fields and to-one associations to be selected */
    const FIELDS_SELECT_WITH_ASSOCIATIONS = '_select_assoc';
    /** @internal Uses for caching the built lists of fields to be serialized */
    const FIELDS_SERIALIZE = '_serialize';

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var DataAccessorInterface */
    protected $dataAccessor;

    /** @var EntityFieldFilterInterface */
    protected $entityFieldFilter;

    /**
     * @param DoctrineHelper                  $doctrineHelper
     * @param DataAccessorInterface           $dataAccessor
     * @param EntityFieldFilterInterface|null $entityFieldFilter
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        DataAccessorInterface $dataAccessor,
        EntityFieldFilterInterface $entityFieldFilter = null
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->dataAccessor = $dataAccessor;
        $this->entityFieldFilter = $entityFieldFilter;
    }

    /**
     * @param string       $entityClass
     * @param EntityConfig $config
     *
     * @return string[]
     */
    public function getFields($entityClass, EntityConfig $config)
    {
        // try to use cached result
        $result = $config->get(self::FIELDS_ALL);
        if (null !== $result) {
            return $result;
        }

        $result = [];
        if ($config->isExcludeAll()) {
            $fieldConfigs = $config->getFields();
            foreach ($fieldConfigs as $field => $fieldConfig) {
                if (!$fieldConfig->isExcluded()) {
                    $result[] = $field;
                }
            }
        } else {
            $entityMetadata = $this->doctrineHelper->getEntityMetadata($entityClass);
            $properties = array_merge($entityMetadata->getFieldNames(), $entityMetadata->getAssociationNames());
            foreach ($properties as $property) {
                $field = $this->getField($config, $property);
                if ($config->hasField($field)) {
                    $fieldConfig = $config->getField($field);
                    if (!$fieldConfig->isExcluded()) {
                        $result[] = $field;
                    }
                } elseif ($this->isApplicableField($entityClass, $property)) {
                    // @todo: ignore not configured relations to avoid infinite loop
                    // it is a temporary fix until the identifier field will not be used by default for them
                    if (!$entityMetadata->isAssociation($field)) {
                        $result[] = $field;
                    }
                }
            }
            $fieldConfigs = $config->getFields();
            foreach ($fieldConfigs as $field => $fieldConfig) {
                if ($this->isMetadataProperty($fieldConfig->getPropertyPath($field))) {
                    $result[] = $field;
                }
            }
        }
        // add result to cache
        $config->set(self::FIELDS_ALL, $result);

        return $result;
    }

    /**
     * @param string       $entityClass
     * @param EntityConfig $config
     * @param bool         $withAssociations
     *
     * @return string[]
     */
    public function getFieldsToSelect($entityClass, EntityConfig $config, $withAssociations = false)
    {
        $cacheKey = self::FIELDS_SELECT;
        if ($withAssociations) {
            $cacheKey = self::FIELDS_SELECT_WITH_ASSOCIATIONS;
        }

        // try to use cached result
        $result = $config->get($cacheKey);
        if (null !== $result) {
            return $result;
        }

        $result = [];
        $entityMetadata = $this->doctrineHelper->getEntityMetadata($entityClass);
        $fields = $this->getFields($entityClass, $config);
        foreach ($fields as $field) {
            $field = $this->getPropertyPath($field, $config->getField($field));
            if ($entityMetadata->isField($field)
                || (
                    $withAssociations
                    && $entityMetadata->isAssociation($field)
                    && !$entityMetadata->isCollectionValuedAssociation($field)
                )
            ) {
                $result[] = $field;
            }
        }
        // make sure identifier fields are added
        $idFields = $entityMetadata->getIdentifierFieldNames();
        foreach ($idFields as $field) {
            if (!in_array($field, $result, true)) {
                $result[] = $field;
            }
        }
        // add result to cache
        $config->set($cacheKey, $result);

        return $result;
    }

    /**
     * @param string       $entityClass
     * @param EntityConfig $config
     *
     * @return string[]
     */
    public function getFieldsToSerialize($entityClass, EntityConfig $config)
    {
        // try to use cached result
        $result = $config->get(self::FIELDS_SERIALIZE);
        if (null !== $result) {
            return $result;
        }

        $result = [];
        $entityMetadata = $this->doctrineHelper->getEntityMetadata($entityClass);
        $fields = $this->getFields($entityClass, $config);
        foreach ($fields as $field) {
            $propertyPath = $this->getPropertyPath($field, $config->getField($field));
            if (!$entityMetadata->isCollectionValuedAssociation($propertyPath)) {
                $result[] = $field;
            }
        }
        // make sure identifier fields are added
        $idField = $this->getIdField($entityClass, $config);
        if (!in_array($idField, $result, true)) {
            $result[] = $idField;
            if ($config->isExcludeAll()) {
                $excludedFields = $config->get(ConfigUtil::EXCLUDED_FIELDS);
                if (null === $excludedFields) {
                    $config->set(ConfigUtil::EXCLUDED_FIELDS, [$idField]);
                } elseif (!in_array($idField, $excludedFields, true)) {
                    $excludedFields[] = $idField;
                    $config->set(ConfigUtil::EXCLUDED_FIELDS, $excludedFields);
                }
            }
        }
        // add result to cache
        $config->set(self::FIELDS_SERIALIZE, $result);

        return $result;
    }

    /**
     * Gets the name of identifier field
     *
     * @param string       $entityClass
     * @param EntityConfig $config
     *
     * @return string
     */
    public function getIdField($entityClass, EntityConfig $config)
    {
        return $this->getField($config, $this->doctrineHelper->getEntityIdFieldName($entityClass));
    }

    /**
     * Checks whether the given property path represents a metadata property
     *
     * @param string $propertyPath
     *
     * @return mixed
     */
    public function isMetadataProperty($propertyPath)
    {
        return ConfigUtil::CLASS_NAME === $propertyPath || ConfigUtil::DISCRIMINATOR === $propertyPath;
    }

    /**
     * Returns a value of a metadata property
     *
     * @param object         $entity
     * @param string         $propertyPath
     * @param EntityMetadata $entityMetadata
     *
     * @return mixed
     */
    public function getMetadataProperty($entity, $propertyPath, $entityMetadata)
    {
        switch ($propertyPath) {
            case ConfigUtil::CLASS_NAME:
                return ClassUtils::getClass($entity);
            case ConfigUtil::DISCRIMINATOR:
                return $entityMetadata->getDiscriminatorValue(ClassUtils::getClass($entity));
            default:
                return null;
        }
    }

    /**
     * @param string $entityClass
     * @param string $field
     *
     * @return bool
     */
    protected function isApplicableField($entityClass, $field)
    {
        if (!$this->dataAccessor->hasGetter($entityClass, $field)) {
            return false;
        }

        return null !== $this->entityFieldFilter
            ? $this->entityFieldFilter->isApplicableField($entityClass, $field)
            : true;
    }

    /**
     * Gets the field name for the given entity property taking into account renaming
     *
     * @param EntityConfig $config
     * @param string       $property
     *
     * @return string
     */
    protected function getField(EntityConfig $config, $property)
    {
        $renamedFields = $config->get(ConfigUtil::RENAMED_FIELDS);
        if (null !== $renamedFields && isset($renamedFields[$property])) {
            return $renamedFields[$property];
        }

        return $property;
    }

    /**
     * Gets the path to entity property for the given field
     *
     * @param string           $fieldName
     * @param FieldConfig|null $fieldConfig
     *
     * @return string
     */
    protected function getPropertyPath($fieldName, FieldConfig $fieldConfig = null)
    {
        if (null === $fieldConfig) {
            return $fieldName;
        }

        return $fieldConfig->getPropertyPath($fieldName);
    }
}
