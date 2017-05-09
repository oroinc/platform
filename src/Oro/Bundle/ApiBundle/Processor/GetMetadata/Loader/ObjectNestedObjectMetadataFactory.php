<?php

namespace Oro\Bundle\ApiBundle\Processor\GetMetadata\Loader;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionFieldConfig;
use Oro\Bundle\ApiBundle\Metadata\AssociationMetadata;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;

class ObjectNestedObjectMetadataFactory
{
    /** @var NestedObjectMetadataHelper */
    protected $nestedObjectMetadataHelper;

    /** @var ObjectMetadataFactory */
    protected $objectMetadataFactory;

    /**
     * @param NestedObjectMetadataHelper $nestedObjectMetadataHelper
     * @param ObjectMetadataFactory      $objectMetadataFactory
     */
    public function __construct(
        NestedObjectMetadataHelper $nestedObjectMetadataHelper,
        ObjectMetadataFactory $objectMetadataFactory
    ) {
        $this->nestedObjectMetadataHelper = $nestedObjectMetadataHelper;
        $this->objectMetadataFactory = $objectMetadataFactory;
    }

    /**
     * @param EntityMetadata              $entityMetadata
     * @param EntityDefinitionConfig      $config
     * @param string                      $entityClass
     * @param string                      $fieldName
     * @param EntityDefinitionFieldConfig $field
     * @param bool                        $withExcludedProperties
     * @param string                      $targetAction
     *
     * @return AssociationMetadata
     */
    public function createAndAddNestedObjectMetadata(
        EntityMetadata $entityMetadata,
        EntityDefinitionConfig $config,
        $entityClass,
        $fieldName,
        EntityDefinitionFieldConfig $field,
        $withExcludedProperties,
        $targetAction
    ) {
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

    /**
     * @param EntityDefinitionConfig $parentConfig
     * @param string                 $parentClassName
     * @param string                 $parentFieldName
     * @param EntityMetadata         $targetEntityMetadata
     * @param EntityDefinitionConfig $targetConfig
     * @param bool                   $withExcludedProperties
     * @param string                 $targetAction
     */
    protected function loadNestedObjectFieldsMetadata(
        EntityDefinitionConfig $parentConfig,
        $parentClassName,
        $parentFieldName,
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
