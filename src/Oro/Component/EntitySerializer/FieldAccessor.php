<?php

namespace Oro\Component\EntitySerializer;

use Doctrine\Common\Util\ClassUtils;

/**
 * Provides a set of methods to get information about fields.
 */
class FieldAccessor
{
    /** Uses for caching the built list of fields */
    private const FIELDS_ALL = '_fields';

    /** Uses for caching the built list of fields to be selected */
    private const FIELDS_SELECT = '_select';

    /** Uses for caching the built lists of fields and to-one associations to be selected */
    private const FIELDS_SELECT_WITH_ASSOCIATIONS = '_select_assoc';

    /** Uses for caching the built lists of fields to be serialized */
    private const FIELDS_SERIALIZE = '_serialize';

    private DoctrineHelper $doctrineHelper;
    private DataAccessorInterface $dataAccessor;
    private ?EntityFieldFilterInterface $entityFieldFilter;

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
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function getFields(string $entityClass, EntityConfig $config): array
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
                    // ignore not configured associations to avoid infinite loop
                    // this can be fixed when the identifier field will not be used by default for them
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
    public function getFieldsToSelect(string $entityClass, EntityConfig $config, bool $withAssociations = false): array
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
                || ($withAssociations && $entityMetadata->isSingleValuedAssociation($field))
            ) {
                $result[] = $field;
            }
        }
        // make sure identifier fields are added
        $idFields = $entityMetadata->getIdentifierFieldNames();
        foreach ($idFields as $field) {
            if (!\in_array($field, $result, true)) {
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
    public function getFieldsToSerialize(string $entityClass, EntityConfig $config): array
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
        if (!\in_array($idField, $result, true)) {
            $result[] = $idField;
            if ($config->isExcludeAll()) {
                $excludedFields = $config->get(ConfigUtil::EXCLUDED_FIELDS);
                if (null === $excludedFields) {
                    $config->set(ConfigUtil::EXCLUDED_FIELDS, [$idField]);
                } elseif (!\in_array($idField, $excludedFields, true)) {
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
     * Gets the name of identifier field.
     */
    public function getIdField(string $entityClass, EntityConfig $config): string
    {
        return $this->getField($config, $this->doctrineHelper->getEntityIdFieldName($entityClass));
    }

    /**
     * Checks whether the given property represents a metadata property.
     */
    public function isMetadataProperty(string $property): bool
    {
        return ConfigUtil::CLASS_NAME === $property || ConfigUtil::DISCRIMINATOR === $property;
    }

    /**
     * Gets a value of a metadata property.
     */
    public function getMetadataProperty(object $entity, string $property, EntityMetadata $entityMetadata): mixed
    {
        switch ($property) {
            case ConfigUtil::CLASS_NAME:
                return ClassUtils::getClass($entity);
            case ConfigUtil::DISCRIMINATOR:
                return $entityMetadata->getDiscriminatorValue(ClassUtils::getClass($entity));
            default:
                return null;
        }
    }

    private function isApplicableField(string $entityClass, string $field): bool
    {
        if (!$this->dataAccessor->hasGetter($entityClass, $field)) {
            return false;
        }

        return
            null === $this->entityFieldFilter
            || $this->entityFieldFilter->isApplicableField($entityClass, $field);
    }

    /**
     * Gets the field name for the given entity property taking into account renaming.
     */
    private function getField(EntityConfig $config, string $property): string
    {
        $renamedFields = $config->get(ConfigUtil::RENAMED_FIELDS);
        if (null !== $renamedFields && isset($renamedFields[$property])) {
            return $renamedFields[$property];
        }

        return $property;
    }

    /**
     * Gets the path to entity property for the given field.
     */
    private function getPropertyPath(string $fieldName, FieldConfig $fieldConfig = null): string
    {
        if (null === $fieldConfig) {
            return $fieldName;
        }

        return $fieldConfig->getPropertyPath($fieldName);
    }
}
