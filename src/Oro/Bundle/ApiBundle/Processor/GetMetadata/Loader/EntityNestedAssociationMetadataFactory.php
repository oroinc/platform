<?php

namespace Oro\Bundle\ApiBundle\Processor\GetMetadata\Loader;

use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionFieldConfig;
use Oro\Bundle\ApiBundle\Metadata\AssociationMetadata;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;

class EntityNestedAssociationMetadataFactory
{
    /** @var NestedAssociationMetadataHelper */
    protected $nestedAssociationMetadataHelper;

    /** @var EntityMetadataFactory */
    protected $entityMetadataFactory;

    /**
     * @param NestedAssociationMetadataHelper $nestedAssociationMetadataHelper
     * @param EntityMetadataFactory           $entityMetadataFactory
     */
    public function __construct(
        NestedAssociationMetadataHelper $nestedAssociationMetadataHelper,
        EntityMetadataFactory $entityMetadataFactory
    ) {
        $this->nestedAssociationMetadataHelper = $nestedAssociationMetadataHelper;
        $this->entityMetadataFactory = $entityMetadataFactory;
    }

    /**
     * @param EntityMetadata              $entityMetadata
     * @param ClassMetadata               $classMetadata
     * @param string                      $entityClass
     * @param string                      $fieldName
     * @param EntityDefinitionFieldConfig $field
     * @param bool                        $withExcludedProperties
     * @param string                      $targetAction
     *
     * @return AssociationMetadata
     */
    public function createAndAddNestedAssociationMetadata(
        EntityMetadata $entityMetadata,
        ClassMetadata $classMetadata,
        $entityClass,
        $fieldName,
        EntityDefinitionFieldConfig $field,
        $withExcludedProperties,
        $targetAction
    ) {
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

    /**
     * @param ClassMetadata          $parentClassMetadata
     * @param EntityMetadata         $targetEntityMetadata
     * @param EntityDefinitionConfig $targetConfig
     * @param bool                   $withExcludedProperties
     * @param string                 $targetAction
     */
    protected function loadNestedAssociationFieldsMetadata(
        ClassMetadata $parentClassMetadata,
        EntityMetadata $targetEntityMetadata,
        EntityDefinitionConfig $targetConfig,
        $withExcludedProperties,
        $targetAction
    ) {
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
