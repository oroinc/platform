<?php

namespace Oro\Bundle\ApiBundle\Processor\GetMetadata\Loader;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionFieldConfig;
use Oro\Bundle\ApiBundle\Metadata\AssociationMetadata;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;

/**
 * The metadata factory for nested associations for non manageable entities.
 */
class ObjectNestedAssociationMetadataFactory
{
    private NestedAssociationMetadataHelper $nestedAssociationMetadataHelper;
    private ObjectMetadataFactory $objectMetadataFactory;

    public function __construct(
        NestedAssociationMetadataHelper $nestedAssociationMetadataHelper,
        ObjectMetadataFactory $objectMetadataFactory
    ) {
        $this->nestedAssociationMetadataHelper = $nestedAssociationMetadataHelper;
        $this->objectMetadataFactory = $objectMetadataFactory;
    }

    public function createAndAddNestedAssociationMetadata(
        EntityMetadata $entityMetadata,
        string $entityClass,
        string $fieldName,
        EntityDefinitionFieldConfig $field,
        bool $withExcludedProperties,
        ?string $targetAction
    ): AssociationMetadata {
        $associationMetadata = $this->nestedAssociationMetadataHelper->addNestedAssociation(
            $entityMetadata,
            $entityClass,
            $fieldName,
            $field,
            $targetAction
        );

        $this->loadNestedAssociationFieldsMetadata(
            $entityClass,
            $associationMetadata->getTargetMetadata(),
            $field->getTargetEntity(),
            $withExcludedProperties,
            $targetAction
        );

        return $associationMetadata;
    }

    private function loadNestedAssociationFieldsMetadata(
        string $parentClassName,
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

            $targetPropertyMetadata = $targetField->isMetaProperty()
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

            $this->nestedAssociationMetadataHelper->setTargetPropertyPath(
                $targetPropertyMetadata,
                $targetFieldName,
                $targetField,
                $targetAction
            );
        }
    }
}
