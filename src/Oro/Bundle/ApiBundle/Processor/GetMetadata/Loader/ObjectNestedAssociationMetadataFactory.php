<?php

namespace Oro\Bundle\ApiBundle\Processor\GetMetadata\Loader;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionFieldConfig;
use Oro\Bundle\ApiBundle\Metadata\AssociationMetadata;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;

class ObjectNestedAssociationMetadataFactory
{
    /** @var NestedAssociationMetadataHelper */
    protected $nestedAssociationMetadataHelper;

    /** @var ObjectMetadataFactory */
    protected $objectMetadataFactory;

    /**
     * @param NestedAssociationMetadataHelper $nestedAssociationMetadataHelper
     * @param ObjectMetadataFactory           $objectMetadataFactory
     */
    public function __construct(
        NestedAssociationMetadataHelper $nestedAssociationMetadataHelper,
        ObjectMetadataFactory $objectMetadataFactory
    ) {
        $this->nestedAssociationMetadataHelper = $nestedAssociationMetadataHelper;
        $this->objectMetadataFactory = $objectMetadataFactory;
    }

    /**
     * @param EntityMetadata              $entityMetadata
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

        $this->loadNestedAssociationFieldsMetadata(
            $entityClass,
            $associationMetadata->getTargetMetadata(),
            $field->getTargetEntity(),
            $withExcludedProperties,
            $targetAction
        );

        return $associationMetadata;
    }

    /**
     * @param string                 $parentClassName
     * @param EntityMetadata         $targetEntityMetadata
     * @param EntityDefinitionConfig $targetConfig
     * @param bool                   $withExcludedProperties
     * @param string                 $targetAction
     */
    protected function loadNestedAssociationFieldsMetadata(
        $parentClassName,
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
