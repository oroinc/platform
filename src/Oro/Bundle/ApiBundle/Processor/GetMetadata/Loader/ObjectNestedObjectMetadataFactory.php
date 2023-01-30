<?php

namespace Oro\Bundle\ApiBundle\Processor\GetMetadata\Loader;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionFieldConfig;
use Oro\Bundle\ApiBundle\Metadata\AssociationMetadata;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;

/**
 * The metadata factory for nested objects for non manageable entities.
 */
class ObjectNestedObjectMetadataFactory
{
    private NestedObjectMetadataHelper $nestedObjectMetadataHelper;
    private ObjectMetadataFactory $objectMetadataFactory;

    public function __construct(
        NestedObjectMetadataHelper $nestedObjectMetadataHelper,
        ObjectMetadataFactory $objectMetadataFactory
    ) {
        $this->nestedObjectMetadataHelper = $nestedObjectMetadataHelper;
        $this->objectMetadataFactory = $objectMetadataFactory;
    }

    public function createAndAddNestedObjectMetadata(
        EntityMetadata $entityMetadata,
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
                ? $this->objectMetadataFactory->createAndAddMetaPropertyMetadata(
                    $targetEntityMetadata,
                    $parentClassName,
                    $targetFieldName,
                    $targetField,
                    $targetAction
                )
                : $this->objectMetadataFactory->createAndAddFieldMetadata(
                    $targetEntityMetadata,
                    $parentClassName,
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
