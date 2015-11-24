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
     * @param DoctrineHelper        $doctrineHelper
     * @param DataAccessorInterface $dataAccessor
     */
    public function __construct(DoctrineHelper $doctrineHelper, DataAccessorInterface $dataAccessor)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->dataAccessor   = $dataAccessor;
    }

    /**
     * @param EntityFieldFilterInterface $entityFieldFilter
     */
    public function setEntityFieldFilter(EntityFieldFilterInterface $entityFieldFilter)
    {
        $this->entityFieldFilter = $entityFieldFilter;
    }

    /**
     * @param array $config
     *
     * @return string
     */
    public function getConfigFields($config)
    {
        if (empty($config[ConfigUtil::FIELDS])) {
            return [];
        } else {
            return array_keys($config[ConfigUtil::FIELDS]);
        }
    }

    /**
     * @param string $entityClass
     * @param array  $config
     *
     * @return string[]
     */
    public function getFields($entityClass, $config)
    {
        $result = [];
        if (ConfigUtil::isExcludeAll($config)) {
            if (!empty($config[ConfigUtil::FIELDS])) {
                foreach ($config[ConfigUtil::FIELDS] as $field => $fieldConfig) {
                    if (null === $fieldConfig || !ConfigUtil::isExclude($fieldConfig)) {
                        $result[] = $field;
                    }
                }
            }
        } else {
            $entityMetadata = $this->doctrineHelper->getEntityMetadata($entityClass);
            $fields         = array_merge($entityMetadata->getFieldNames(), $entityMetadata->getAssociationNames());
            foreach ($fields as $field) {
                if (ConfigUtil::hasFieldConfig($config, $field)) {
                    $fieldConfig = $config[ConfigUtil::FIELDS][$field];
                    if (null === $fieldConfig || !ConfigUtil::isExclude($fieldConfig)) {
                        $result[] = $field;
                    }
                } elseif ($this->isApplicableField($entityClass, $field)) {
                    $result[] = $field;
                }
            }
            if (!empty($config[ConfigUtil::FIELDS])) {
                foreach ($config[ConfigUtil::FIELDS] as $field => $fieldConfig) {
                    if ($this->isMetadataProperty($field)) {
                        $result[] = $field;
                    }
                }
            }
        }

        return $result;
    }

    /**
     * @param string $entityClass
     * @param array  $config
     * @param bool   $withAssociations
     *
     * @return string[]
     */
    public function getFieldsToSelect($entityClass, $config, $withAssociations = false)
    {
        $entityMetadata = $this->doctrineHelper->getEntityMetadata($entityClass);
        $fields         = array_filter(
            $this->getFields($entityClass, $config),
            function ($field) use ($entityMetadata, $config, $withAssociations) {
                // skip metadata properties like '__class__' or '__discriminator__'
                if ($this->isMetadataProperty($field)) {
                    return false;
                }
                // skip virtual properties
                if (isset($config[ConfigUtil::FIELDS][$field][ConfigUtil::PROPERTY_PATH])) {
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
     * @param string $entityClass
     * @param array  $config
     *
     * @return string[]
     */
    public function getFieldsToSerialize($entityClass, $config)
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
     * Checks whether a property path represents some metadata property
     *
     * @param string $propertyPath
     *
     * @return bool
     */
    public function isMetadataProperty($propertyPath)
    {
        return ConfigUtil::isMetadataProperty($propertyPath);
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
