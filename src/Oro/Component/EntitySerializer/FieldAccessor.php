<?php

namespace Oro\Component\EntitySerializer;

use Doctrine\Common\Util\ClassUtils;

class FieldAccessor
{
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
        $this->doctrineHelper    = $doctrineHelper;
        $this->dataAccessor      = $dataAccessor;
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
            $fields         = array_merge($entityMetadata->getFieldNames(), $entityMetadata->getAssociationNames());
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
        $entityMetadata = $this->doctrineHelper->getEntityMetadata($entityClass);
        $fields         = array_filter(
            $this->getFields($entityClass, $config),
            function ($field) use ($entityMetadata, $withAssociations) {
                // skip virtual properties
                if (!$entityMetadata->isField($field) && !$entityMetadata->isAssociation($field)) {
                    return false;
                }

                return $withAssociations
                    ? !$entityMetadata->isCollectionValuedAssociation($field)
                    : !$entityMetadata->isAssociation($field);
            }
        );
        // make sure identifier fields are added
        foreach ($entityMetadata->getIdentifierFieldNames() as $field) {
            if (!in_array($field, $fields, true)) {
                $fields[] = $field;
            }
        }

        return $fields;
    }

    /**
     * @param string       $entityClass
     * @param EntityConfig $config
     *
     * @return string[]
     */
    public function getFieldsToSerialize($entityClass, EntityConfig $config)
    {
        $entityMetadata = $this->doctrineHelper->getEntityMetadata($entityClass);

        return array_filter(
            $this->getFields($entityClass, $config),
            function ($field) use ($entityMetadata) {
                return !$entityMetadata->isCollectionValuedAssociation($field);
            }
        );
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
