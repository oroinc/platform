<?php

namespace Oro\Bundle\ApiBundle\Processor\GetMetadata\Loader;

use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadataFactory as MetadataFactory;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\ApiBundle\Util\EntityIdHelper;

/**
 * The metadata loader for manageable entities.
 */
class EntityMetadataLoader
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var EntityIdHelper */
    protected $entityIdHelper;

    /** @var MetadataFactory */
    protected $metadataFactory;

    /** @var ObjectMetadataFactory */
    protected $objectMetadataFactory;

    /** @var EntityMetadataFactory */
    protected $entityMetadataFactory;

    /** @var EntityNestedObjectMetadataFactory */
    protected $nestedObjectMetadataFactory;

    /** @var EntityNestedAssociationMetadataFactory */
    protected $nestedAssociationMetadataFactory;

    /**
     * @param DoctrineHelper                         $doctrineHelper
     * @param EntityIdHelper                         $entityIdHelper
     * @param MetadataFactory                        $metadataFactory
     * @param ObjectMetadataFactory                  $objectMetadataFactory
     * @param EntityMetadataFactory                  $entityMetadataFactory
     * @param EntityNestedObjectMetadataFactory      $nestedObjectMetadataFactory
     * @param EntityNestedAssociationMetadataFactory $nestedAssociationMetadataFactory
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        EntityIdHelper $entityIdHelper,
        MetadataFactory $metadataFactory,
        ObjectMetadataFactory $objectMetadataFactory,
        EntityMetadataFactory $entityMetadataFactory,
        EntityNestedObjectMetadataFactory $nestedObjectMetadataFactory,
        EntityNestedAssociationMetadataFactory $nestedAssociationMetadataFactory
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->entityIdHelper = $entityIdHelper;
        $this->metadataFactory = $metadataFactory;
        $this->objectMetadataFactory = $objectMetadataFactory;
        $this->entityMetadataFactory = $entityMetadataFactory;
        $this->nestedObjectMetadataFactory = $nestedObjectMetadataFactory;
        $this->nestedAssociationMetadataFactory = $nestedAssociationMetadataFactory;
    }

    /**
     * @param string                 $entityClass
     * @param EntityDefinitionConfig $config
     * @param bool                   $withExcludedProperties
     * @param string                 $targetAction
     *
     * @return EntityMetadata
     */
    public function loadEntityMetadata(
        $entityClass,
        EntityDefinitionConfig $config,
        $withExcludedProperties,
        $targetAction
    ) {
        // filter excluded fields on this stage though there is another processor doing the same
        // it is done due to performance reasons
        $allowedFields = $this->getAllowedFields($config, $withExcludedProperties);

        $classMetadata = $this->doctrineHelper->getEntityMetadataForClass($entityClass);
        $entityMetadata = $this->createEntityMetadata($classMetadata, $config);
        $this->loadEntityFieldsMetadata(
            $entityMetadata,
            $classMetadata,
            $allowedFields,
            $config,
            $targetAction
        );
        $this->loadEntityAssociationsMetadata(
            $entityMetadata,
            $classMetadata,
            $allowedFields,
            $config,
            $targetAction
        );
        $this->loadEntityPropertiesMetadata(
            $entityMetadata,
            $classMetadata,
            $config,
            $withExcludedProperties,
            $targetAction
        );

        return $entityMetadata;
    }

    /**
     * @param EntityDefinitionConfig $config
     * @param bool                   $withExcludedProperties
     *
     * @return array [property path => field name, ...]
     */
    protected function getAllowedFields(EntityDefinitionConfig $config, $withExcludedProperties)
    {
        $result = [];
        $fields = $config->getFields();
        foreach ($fields as $fieldName => $field) {
            if ($withExcludedProperties || !$field->isExcluded()) {
                $propertyPath = $field->getPropertyPath($fieldName);
                if (ConfigUtil::IGNORE_PROPERTY_PATH !== $propertyPath) {
                    $result[$propertyPath] = $fieldName;
                }
            }
        }

        return $result;
    }

    /**
     * @param ClassMetadata          $classMetadata
     * @param EntityDefinitionConfig $config
     *
     * @return EntityMetadata
     */
    protected function createEntityMetadata(ClassMetadata $classMetadata, EntityDefinitionConfig $config)
    {
        $entityMetadata = $this->metadataFactory->createEntityMetadata($classMetadata);
        $configuredIdFieldNames = $config->getIdentifierFieldNames();
        if (!empty($configuredIdFieldNames)) {
            if ($entityMetadata->hasIdentifierGenerator()
                && !$this->entityIdHelper->isEntityIdentifierEqual($entityMetadata->getIdentifierFieldNames(), $config)
            ) {
                $entityMetadata->setHasIdentifierGenerator(false);
            }
            $entityMetadata->setIdentifierFieldNames($configuredIdFieldNames);
        } else {
            $idFieldNames = $entityMetadata->getIdentifierFieldNames();
            if (!empty($idFieldNames)) {
                $normalizedIdFieldNames = [];
                foreach ($idFieldNames as $propertyPath) {
                    $fieldName = $config->findFieldNameByPropertyPath($propertyPath);
                    $normalizedIdFieldNames[] = $fieldName ?: $propertyPath;
                }
                $entityMetadata->setIdentifierFieldNames($normalizedIdFieldNames);
            }
        }

        return $entityMetadata;
    }

    /**
     * @param EntityMetadata         $entityMetadata
     * @param ClassMetadata          $classMetadata
     * @param array                  $allowedFields
     * @param EntityDefinitionConfig $config
     * @param string                 $targetAction
     */
    protected function loadEntityFieldsMetadata(
        EntityMetadata $entityMetadata,
        ClassMetadata $classMetadata,
        array $allowedFields,
        EntityDefinitionConfig $config,
        $targetAction
    ) {
        $fields = $classMetadata->getFieldNames();
        foreach ($fields as $propertyPath) {
            if (!isset($allowedFields[$propertyPath])) {
                continue;
            }
            $fieldName = $allowedFields[$propertyPath];
            $field = $config->getField($fieldName);
            if ($field->isMetaProperty()) {
                $metadata = $this->entityMetadataFactory->createAndAddMetaPropertyMetadata(
                    $entityMetadata,
                    $classMetadata,
                    $fieldName,
                    $field,
                    $targetAction
                );
            } else {
                $metadata = $this->entityMetadataFactory->createAndAddFieldMetadata(
                    $entityMetadata,
                    $classMetadata,
                    $fieldName,
                    $field,
                    $targetAction
                );
            }
            if ($field->hasDirection()) {
                $metadata->setDirection($field->isInput(), $field->isOutput());
            }
        }
    }

    /**
     * @param EntityMetadata         $entityMetadata
     * @param ClassMetadata          $classMetadata
     * @param array                  $allowedFields
     * @param EntityDefinitionConfig $config
     * @param string                 $targetAction
     */
    protected function loadEntityAssociationsMetadata(
        EntityMetadata $entityMetadata,
        ClassMetadata $classMetadata,
        array $allowedFields,
        EntityDefinitionConfig $config,
        $targetAction
    ) {
        $associations = $classMetadata->getAssociationNames();
        foreach ($associations as $propertyPath) {
            if (!isset($allowedFields[$propertyPath])) {
                continue;
            }
            $associationName = $allowedFields[$propertyPath];
            $field = $config->getField($associationName);
            $metadata = $this->entityMetadataFactory->createAndAddAssociationMetadata(
                $entityMetadata,
                $classMetadata,
                $associationName,
                $field,
                $targetAction
            );
            if ($field->hasDirection()) {
                $metadata->setDirection($field->isInput(), $field->isOutput());
            }
        }
    }

    /**
     * @param EntityMetadata         $entityMetadata
     * @param ClassMetadata          $classMetadata
     * @param EntityDefinitionConfig $config
     * @param bool                   $withExcludedProperties
     * @param string                 $targetAction
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function loadEntityPropertiesMetadata(
        EntityMetadata $entityMetadata,
        ClassMetadata $classMetadata,
        EntityDefinitionConfig $config,
        $withExcludedProperties,
        $targetAction
    ) {
        $entityClass = $entityMetadata->getClassName();
        $fields = $config->getFields();
        foreach ($fields as $fieldName => $field) {
            if (!$withExcludedProperties && $field->isExcluded()) {
                continue;
            }
            $metadata = null;
            if (!$field->isMetaProperty()) {
                $dataType = $field->getDataType();
                if (!$entityMetadata->hasField($fieldName) && !$entityMetadata->hasAssociation($fieldName)) {
                    if ($dataType && DataType::isNestedObject($dataType)) {
                        $metadata = $this->nestedObjectMetadataFactory->createAndAddNestedObjectMetadata(
                            $entityMetadata,
                            $classMetadata,
                            $config,
                            $entityClass,
                            $fieldName,
                            $field,
                            $withExcludedProperties,
                            $targetAction
                        );
                    } elseif ($dataType && DataType::isNestedAssociation($dataType)) {
                        $metadata = $this->nestedAssociationMetadataFactory->createAndAddNestedAssociationMetadata(
                            $entityMetadata,
                            $classMetadata,
                            $entityClass,
                            $fieldName,
                            $field,
                            $withExcludedProperties,
                            $targetAction
                        );
                    } elseif ($field->getTargetClass()) {
                        $metadata = $this->objectMetadataFactory->createAndAddAssociationMetadata(
                            $entityMetadata,
                            $entityClass,
                            $config,
                            $fieldName,
                            $field,
                            $targetAction
                        );
                    } elseif ($dataType) {
                        $metadata = $this->objectMetadataFactory->createAndAddFieldMetadata(
                            $entityMetadata,
                            $entityClass,
                            $fieldName,
                            $field,
                            $targetAction
                        );
                    }
                }
            } elseif (!$entityMetadata->hasMetaProperty($fieldName)) {
                $metadata = $this->objectMetadataFactory->createAndAddMetaPropertyMetadata(
                    $entityMetadata,
                    $entityClass,
                    $fieldName,
                    $field,
                    $targetAction
                );
            }
            if (null !== $metadata && $field->hasDirection()) {
                $metadata->setDirection($field->isInput(), $field->isOutput());
            }
        }
    }
}
