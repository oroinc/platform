<?php

namespace Oro\Bundle\ApiBundle\Processor\GetMetadata\Loader;

use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionFieldConfig;
use Oro\Bundle\ApiBundle\Metadata\AssociationMetadata;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;

class EntityNestedObjectMetadataFactory
{
    /** @var NestedObjectMetadataHelper */
    protected $nestedObjectMetadataHelper;

    /** @var EntityMetadataFactory */
    protected $entityMetadataFactory;

    /**
     * @param NestedObjectMetadataHelper $nestedObjectMetadataHelper
     * @param EntityMetadataFactory      $entityMetadataFactory
     */
    public function __construct(
        NestedObjectMetadataHelper $nestedObjectMetadataHelper,
        EntityMetadataFactory $entityMetadataFactory
    ) {
        $this->nestedObjectMetadataHelper = $nestedObjectMetadataHelper;
        $this->entityMetadataFactory = $entityMetadataFactory;
    }

    /**
     * @param EntityMetadata              $entityMetadata
     * @param ClassMetadata               $classMetadata
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
        ClassMetadata $classMetadata,
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

    /**
     * @param ClassMetadata          $parentClassMetadata
     * @param EntityDefinitionConfig $parentConfig
     * @param string                 $parentClassName
     * @param string                 $parentFieldName
     * @param EntityMetadata         $targetEntityMetadata
     * @param EntityDefinitionConfig $targetConfig
     * @param bool                   $withExcludedProperties
     * @param string                 $targetAction
     */
    protected function loadNestedObjectFieldsMetadata(
        ClassMetadata $parentClassMetadata,
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
