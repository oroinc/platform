<?php

namespace Oro\Component\EntitySerializer;

use Doctrine\Common\Util\ClassUtils;

class FieldAccessor
{
    const KEY_FIELDS_ALL                         = 'fields';
    const KEY_FIELDS_TO_SELECT                   = 'select';
    const KEY_FIELDS_TO_SELECT_WITH_ASSOCIATIONS = 'select_assoc';
    const KEY_FIELDS_TO_SERIALIZE                = 'serialize';

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
        $result = $config->get(self::KEY_FIELDS_ALL);
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
            $fields = array_merge($entityMetadata->getFieldNames(), $entityMetadata->getAssociationNames());
            foreach ($fields as $field) {
                if ($config->hasField($field)) {
                    $fieldConfig = $config->getField($field);
                    if (!$fieldConfig->isExcluded()) {
                        $result[] = $field;
                    }
                } elseif ($this->isApplicableField($entityClass, $field)) {
                    // @todo: ignore not configured relations to avoid infinite loop
                    // it is a temporary fix until the identifier field will not be used by default for them
                    if (!$entityMetadata->isAssociation($field)) {
                        $result[] = $field;
                    }
                }
            }
            $fieldConfigs = $config->getFields();
            foreach ($fieldConfigs as $field => $fieldConfig) {
                if (ConfigUtil::isMetadataProperty($fieldConfig->getPropertyPath() ?: $field)) {
                    $result[] = $field;
                }
            }
        }
        // add result to cache
        $config->set(self::KEY_FIELDS_ALL, $result);

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
        // try to use cached result
        $cacheKey = $withAssociations
            ? self::KEY_FIELDS_TO_SELECT_WITH_ASSOCIATIONS
            : self::KEY_FIELDS_TO_SELECT;
        $result = $config->get($cacheKey);
        if (null !== $result) {
            return $result;
        }

        $result = [];
        $entityMetadata = $this->doctrineHelper->getEntityMetadata($entityClass);
        $fields = $this->getFields($entityClass, $config);
        foreach ($fields as $field) {
            if ($entityMetadata->isField($field)) {
                $result[] = $field;
            } elseif ($withAssociations
                && $entityMetadata->isAssociation($field)
                && !$entityMetadata->isCollectionValuedAssociation($field)
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
        $result = $config->get(self::KEY_FIELDS_TO_SERIALIZE);
        if (null !== $result) {
            return $result;
        }

        $result = [];
        $entityMetadata = $this->doctrineHelper->getEntityMetadata($entityClass);
        $fields = $this->getFields($entityClass, $config);
        foreach ($fields as $field) {
            if (!$entityMetadata->isCollectionValuedAssociation($field)) {
                $result[] = $field;
            }
        }
        // add result to cache
        $config->set(self::KEY_FIELDS_TO_SERIALIZE, $result);

        return $result;
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
            case ConfigUtil::DISCRIMINATOR:
                return $entityMetadata->getDiscriminatorValue(ClassUtils::getClass($entity));
            case ConfigUtil::CLASS_NAME:
                return ClassUtils::getClass($entity);
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
}
