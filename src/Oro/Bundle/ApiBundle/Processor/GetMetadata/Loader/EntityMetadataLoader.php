<?php

namespace Oro\Bundle\ApiBundle\Processor\GetMetadata\Loader;

use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionFieldConfig;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadataFactory as MetadataFactory;
use Oro\Bundle\ApiBundle\Metadata\PropertyMetadata;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\ApiBundle\Util\EntityIdHelper;

/**
 * The metadata loader for manageable entities.
 */
class EntityMetadataLoader
{
    private DoctrineHelper $doctrineHelper;
    private EntityIdHelper $entityIdHelper;
    private MetadataFactory $metadataFactory;
    private ObjectMetadataFactory $objectMetadataFactory;
    private EntityMetadataFactory $entityMetadataFactory;
    private EntityNestedObjectMetadataFactory $nestedObjectMetadataFactory;
    private EntityNestedAssociationMetadataFactory $nestedAssociationMetadataFactory;

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

    public function loadEntityMetadata(
        string $entityClass,
        EntityDefinitionConfig $config,
        bool $withExcludedProperties,
        ?string $targetAction
    ): EntityMetadata {
        // filter excluded fields on this stage though there is another processor doing the same
        // it is done due to performance reasons
        $allowedFields = $this->getAllowedFields($config, $withExcludedProperties);

        /** @var ClassMetadata $classMetadata */
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
     * @return array [property path => field name, ...]
     */
    private function getAllowedFields(EntityDefinitionConfig $config, bool $withExcludedProperties): array
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

    private function createEntityMetadata(ClassMetadata $classMetadata, EntityDefinitionConfig $config): EntityMetadata
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
                    $normalizedIdFieldNames[] = $config->findFieldNameByPropertyPath($propertyPath) ?? $propertyPath;
                }
                $entityMetadata->setIdentifierFieldNames($normalizedIdFieldNames);
            }
        }

        return $entityMetadata;
    }

    private function loadEntityFieldsMetadata(
        EntityMetadata $entityMetadata,
        ClassMetadata $classMetadata,
        array $allowedFields,
        EntityDefinitionConfig $config,
        ?string $targetAction
    ): void {
        $fields = $classMetadata->getFieldNames();
        foreach ($fields as $propertyPath) {
            if (!isset($allowedFields[$propertyPath])) {
                continue;
            }
            $fieldName = $allowedFields[$propertyPath];
            /** @var EntityDefinitionFieldConfig $field */
            $field = $config->getField($fieldName);
            $metadata = null;
            if ($field->isMetaProperty()) {
                $metadata = $this->entityMetadataFactory->createAndAddMetaPropertyMetadata(
                    $entityMetadata,
                    $classMetadata,
                    $fieldName,
                    $field,
                    $targetAction
                );
            } elseif (!$field->getTargetClass()) {
                $metadata = $this->entityMetadataFactory->createAndAddFieldMetadata(
                    $entityMetadata,
                    $classMetadata,
                    $fieldName,
                    $field,
                    $targetAction
                );
            }
            $this->setDirection($metadata, $field);
        }
    }

    private function loadEntityAssociationsMetadata(
        EntityMetadata $entityMetadata,
        ClassMetadata $classMetadata,
        array $allowedFields,
        EntityDefinitionConfig $config,
        ?string $targetAction
    ): void {
        $associations = $classMetadata->getAssociationNames();
        foreach ($associations as $propertyPath) {
            if (!isset($allowedFields[$propertyPath])) {
                continue;
            }
            $associationName = $allowedFields[$propertyPath];
            /** @var EntityDefinitionFieldConfig $field */
            $field = $config->getField($associationName);
            $metadata = $this->entityMetadataFactory->createAndAddAssociationMetadata(
                $entityMetadata,
                $classMetadata,
                $associationName,
                $field,
                $targetAction
            );
            $this->setDirection($metadata, $field);
        }
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function loadEntityPropertiesMetadata(
        EntityMetadata $entityMetadata,
        ClassMetadata $classMetadata,
        EntityDefinitionConfig $config,
        bool $withExcludedProperties,
        ?string $targetAction
    ): void {
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
                            $targetAction,
                            $field->getTargetClass()
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
            $this->setDirection($metadata, $field);
        }
    }

    private function setDirection(?PropertyMetadata $metadata, EntityDefinitionFieldConfig $field): void
    {
        if (null !== $metadata && $field->hasDirection()) {
            $metadata->setDirection($field->isInput(), $field->isOutput());
        }
    }
}
