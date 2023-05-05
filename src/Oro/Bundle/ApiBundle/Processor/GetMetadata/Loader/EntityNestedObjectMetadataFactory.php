<?php

namespace Oro\Bundle\ApiBundle\Processor\GetMetadata\Loader;

use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionFieldConfig;
use Oro\Bundle\ApiBundle\Metadata\AssociationMetadata;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;

/**
 * The metadata factory for nested objects for manageable entities.
 */
class EntityNestedObjectMetadataFactory
{
    private NestedObjectMetadataHelper $nestedObjectMetadataHelper;
    private EntityMetadataFactory $entityMetadataFactory;

    public function __construct(
        NestedObjectMetadataHelper $nestedObjectMetadataHelper,
        EntityMetadataFactory $entityMetadataFactory
    ) {
        $this->nestedObjectMetadataHelper = $nestedObjectMetadataHelper;
        $this->entityMetadataFactory = $entityMetadataFactory;
    }

    public function createAndAddNestedObjectMetadata(
        EntityMetadata $entityMetadata,
        ClassMetadata $classMetadata,
        EntityDefinitionConfig $config,
        string $entityClass,
        string $fieldName,
        EntityDefinitionFieldConfig $field,
        bool $withExcludedProperties,
        ?string $targetAction
    ): AssociationMetadata {
        $associationMetadata = $this->nestedObjectMetadataHelper->addNestedObjectAssociation(
            $entityMetadata,
            $entityClass,
            $config,
            $fieldName,
            $field,
            $targetAction
        );

        $this->loadNestedObjectFieldsMetadata(
            $classMetadata,
            $config,
            $entityClass,
            $fieldName,
            $associationMetadata->getTargetMetadata(),
            $field->getTargetEntity(),
            $withExcludedProperties,
            $targetAction
        );

        return $associationMetadata;
    }

    private function loadNestedObjectFieldsMetadata(
        ClassMetadata $parentClassMetadata,
        EntityDefinitionConfig $parentConfig,
        string $parentClassName,
        string $parentFieldName,
        EntityMetadata $targetEntityMetadata,
        EntityDefinitionConfig $targetConfig,
        bool $withExcludedProperties,
        ?string $targetAction
    ): void {
        $targetFields = $targetConfig->getFields();
        foreach ($targetFields as $targetFieldName => $targetField) {
            if (!$withExcludedProperties && $targetField->isExcluded()) {
                continue;
            }

            $linkedField = $this->nestedObjectMetadataHelper->getLinkedField(
                $parentConfig,
                $parentClassName,
                $parentFieldName,
                $targetFieldName,
                $targetField
            );

            $targetPropertyMetadata = $linkedField->isMetaProperty()
                ? $this->entityMetadataFactory->createAndAddMetaPropertyMetadata(
                    $targetEntityMetadata,
                    $parentClassMetadata,
                    $targetFieldName,
                    $targetField,
                    $targetAction
                )
                : $this->entityMetadataFactory->createAndAddFieldMetadata(
                    $targetEntityMetadata,
                    $parentClassMetadata,
                    $targetFieldName,
                    $targetField,
                    $targetAction
                );

            $this->nestedObjectMetadataHelper->setTargetPropertyPath(
                $targetPropertyMetadata,
                $targetFieldName,
                $targetField,
                $targetAction
            );
        }
    }
}
