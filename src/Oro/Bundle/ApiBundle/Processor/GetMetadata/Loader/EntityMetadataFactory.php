<?php

namespace Oro\Bundle\ApiBundle\Processor\GetMetadata\Loader;

use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionFieldConfig;
use Oro\Bundle\ApiBundle\Metadata\AssociationMetadata;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadataFactory as MetadataFactory;
use Oro\Bundle\ApiBundle\Metadata\FieldMetadata;
use Oro\Bundle\ApiBundle\Metadata\MetaPropertyMetadata;

/**
 * The metadata factory for manageable entities.
 */
class EntityMetadataFactory
{
    private MetadataHelper $metadataHelper;
    private MetadataFactory $metadataFactory;

    public function __construct(
        MetadataHelper $metadataHelper,
        MetadataFactory $metadataFactory
    ) {
        $this->metadataHelper = $metadataHelper;
        $this->metadataFactory = $metadataFactory;
    }

    public function createAndAddMetaPropertyMetadata(
        EntityMetadata $entityMetadata,
        ClassMetadata $classMetadata,
        string $fieldName,
        EntityDefinitionFieldConfig $field,
        ?string $targetAction
    ): MetaPropertyMetadata {
        $propertyPath = $field->getPropertyPath($fieldName);
        $metaPropertyMetadata = $this->metadataFactory->createMetaPropertyMetadata(
            $classMetadata,
            $propertyPath,
            $field->getDataType()
        );
        if ($propertyPath !== $fieldName) {
            $metaPropertyMetadata->setName($fieldName);
        }
        $this->metadataHelper->setPropertyPath($metaPropertyMetadata, $fieldName, $field, $targetAction);
        $resultName = $field->getMetaPropertyResultName();
        if ($resultName) {
            $metaPropertyMetadata->setResultName($resultName);
        }
        $entityMetadata->addMetaProperty($metaPropertyMetadata);

        return $metaPropertyMetadata;
    }

    public function createAndAddFieldMetadata(
        EntityMetadata $entityMetadata,
        ClassMetadata $classMetadata,
        string $fieldName,
        EntityDefinitionFieldConfig $field,
        ?string $targetAction
    ): FieldMetadata {
        $propertyPath = $field->getPropertyPath($fieldName);
        $fieldMetadata = $this->metadataFactory->createFieldMetadata(
            $classMetadata,
            $propertyPath,
            $field->getDataType()
        );
        if ($propertyPath !== $fieldName) {
            $fieldMetadata->setName($fieldName);
        }
        $this->metadataHelper->setPropertyPath($fieldMetadata, $fieldName, $field, $targetAction);
        $entityMetadata->addField($fieldMetadata);

        return $fieldMetadata;
    }

    public function createAndAddAssociationMetadata(
        EntityMetadata $entityMetadata,
        ClassMetadata $classMetadata,
        string $associationName,
        EntityDefinitionFieldConfig $field,
        ?string $targetAction
    ): AssociationMetadata {
        $propertyPath = $field->getPropertyPath($associationName);
        $associationMetadata = $this->metadataFactory->createAssociationMetadata(
            $classMetadata,
            $propertyPath,
            $field->getDataType()
        );
        if ($propertyPath !== $associationName) {
            $associationMetadata->setName($associationName);
        }
        $this->metadataHelper->setPropertyPath($associationMetadata, $associationName, $field, $targetAction);
        $associationMetadata->setCollapsed($field->isCollapsed());
        $entityMetadata->addAssociation($associationMetadata);

        return $associationMetadata;
    }
}
