<?php

namespace Oro\Bundle\ApiBundle\Processor\GetMetadata\Loader;

use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionFieldConfig;
use Oro\Bundle\ApiBundle\Metadata\AssociationMetadata;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;

/**
 * The metadata factory for nested associations for manageable entities.
 */
class EntityNestedAssociationMetadataFactory
{
    private NestedAssociationMetadataHelper $nestedAssociationMetadataHelper;
    private EntityMetadataFactory $entityMetadataFactory;

    public function __construct(
        NestedAssociationMetadataHelper $nestedAssociationMetadataHelper,
        EntityMetadataFactory $entityMetadataFactory
    ) {
        $this->nestedAssociationMetadataHelper = $nestedAssociationMetadataHelper;
        $this->entityMetadataFactory = $entityMetadataFactory;
    }

    public function createAndAddNestedAssociationMetadata(
        EntityMetadata $entityMetadata,
        ClassMetadata $classMetadata,
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

        $targetConfig = $field->getTargetEntity();
        $this->loadNestedAssociationFieldsMetadata(
            $classMetadata,
            $associationMetadata->getTargetMetadata(),
            $targetConfig,
            $withExcludedProperties,
            $targetAction
        );

        if (!$associationMetadata->getDataType()) {
            $idFieldName = $this->nestedAssociationMetadataHelper->getIdentifierFieldName();
            $idFieldPropertyPath = $targetConfig
                ->getField($idFieldName)
                ->getPropertyPath($idFieldName);
            if ($classMetadata->hasField($idFieldPropertyPath)) {
                $associationMetadata->setDataType($classMetadata->getTypeOfField($idFieldPropertyPath));
            }
        }

        return $associationMetadata;
    }

    private function loadNestedAssociationFieldsMetadata(
        ClassMetadata $parentClassMetadata,
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

            $this->nestedAssociationMetadataHelper->setTargetPropertyPath(
                $targetPropertyMetadata,
                $targetFieldName,
                $targetField,
                $targetAction
            );
        }
    }
}
