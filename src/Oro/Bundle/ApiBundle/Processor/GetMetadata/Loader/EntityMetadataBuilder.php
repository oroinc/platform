<?php

namespace Oro\Bundle\ApiBundle\Processor\GetMetadata\Loader;

use Doctrine\ORM\Mapping\ClassMetadata;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionFieldConfig;
use Oro\Bundle\ApiBundle\Metadata\AssociationMetadata;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadataFactory;
use Oro\Bundle\ApiBundle\Metadata\FieldMetadata;
use Oro\Bundle\ApiBundle\Metadata\MetaPropertyMetadata;

class EntityMetadataBuilder
{
    /** @var MetadataHelper */
    protected $metadataHelper;

    /** @var EntityMetadataFactory */
    protected $entityMetadataFactory;

    /**
     * @param MetadataHelper        $metadataHelper
     * @param EntityMetadataFactory $entityMetadataFactory
     */
    public function __construct(
        MetadataHelper $metadataHelper,
        EntityMetadataFactory $entityMetadataFactory
    ) {
        $this->metadataHelper = $metadataHelper;
        $this->entityMetadataFactory = $entityMetadataFactory;
    }

    /**
     * @param EntityMetadata              $entityMetadata
     * @param ClassMetadata               $classMetadata
     * @param string                      $fieldName
     * @param EntityDefinitionFieldConfig $field
     * @param string                      $targetAction
     *
     * @return MetaPropertyMetadata
     */
    public function addEntityMetaPropertyMetadata(
        EntityMetadata $entityMetadata,
        ClassMetadata $classMetadata,
        $fieldName,
        EntityDefinitionFieldConfig $field,
        $targetAction
    ) {
        $propertyPath = $field->getPropertyPath($fieldName);
        $metaPropertyMetadata = $this->entityMetadataFactory->createMetaPropertyMetadata(
            $classMetadata,
            $propertyPath,
            $field->getDataType()
        );
        if ($propertyPath !== $fieldName) {
            $metaPropertyMetadata->setName($fieldName);
        }
        $this->metadataHelper->setPropertyPath($metaPropertyMetadata, $fieldName, $field, $targetAction);
        $entityMetadata->addMetaProperty($metaPropertyMetadata);

        return $metaPropertyMetadata;
    }

    /**
     * @param EntityMetadata              $entityMetadata
     * @param ClassMetadata               $classMetadata
     * @param string                      $fieldName
     * @param EntityDefinitionFieldConfig $field
     * @param string                      $targetAction
     *
     * @return FieldMetadata
     */
    public function addEntityFieldMetadata(
        EntityMetadata $entityMetadata,
        ClassMetadata $classMetadata,
        $fieldName,
        EntityDefinitionFieldConfig $field,
        $targetAction
    ) {
        $propertyPath = $field->getPropertyPath($fieldName);
        $fieldMetadata = $this->entityMetadataFactory->createFieldMetadata(
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

    /**
     * @param EntityMetadata              $entityMetadata
     * @param ClassMetadata               $classMetadata
     * @param string                      $associationName
     * @param EntityDefinitionFieldConfig $field
     * @param string                      $targetAction
     *
     * @return AssociationMetadata
     */
    public function addEntityAssociationMetadata(
        EntityMetadata $entityMetadata,
        ClassMetadata $classMetadata,
        $associationName,
        EntityDefinitionFieldConfig $field,
        $targetAction
    ) {
        $propertyPath = $field->getPropertyPath($associationName);
        $associationMetadata = $this->entityMetadataFactory->createAssociationMetadata(
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
