<?php

namespace Oro\Bundle\ApiBundle\Processor\GetMetadata\Loader;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionFieldConfig;
use Oro\Bundle\ApiBundle\Exception\RuntimeException;
use Oro\Bundle\ApiBundle\Metadata\AssociationMetadata;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Metadata\PropertyMetadata;

/**
 * Provides methods to build metadata for nested objects.
 */
class NestedObjectMetadataHelper
{
    /** @var MetadataHelper */
    private $metadataHelper;

    /** @var ObjectMetadataFactory */
    private $objectMetadataFactory;

    /**
     * @param MetadataHelper        $metadataHelper
     * @param ObjectMetadataFactory $objectMetadataFactory
     */
    public function __construct(
        MetadataHelper $metadataHelper,
        ObjectMetadataFactory $objectMetadataFactory
    ) {
        $this->metadataHelper = $metadataHelper;
        $this->objectMetadataFactory = $objectMetadataFactory;
    }

    /**
     * @param EntityMetadata              $entityMetadata
     * @param string                      $entityClass
     * @param EntityDefinitionConfig      $config
     * @param string                      $fieldName
     * @param EntityDefinitionFieldConfig $field
     * @param string                      $targetAction
     *
     * @return AssociationMetadata
     */
    public function addNestedObjectAssociation(
        EntityMetadata $entityMetadata,
        $entityClass,
        EntityDefinitionConfig $config,
        $fieldName,
        EntityDefinitionFieldConfig $field,
        $targetAction
    ) {
        $formOptions = $field->getFormOptions();
        if (empty($formOptions['data_class'])) {
            throw new RuntimeException(sprintf(
                'The "data_class" form option should be specified for the nested object. Field: %s::%s.',
                $entityClass,
                $fieldName
            ));
        }
        $targetClass = $formOptions['data_class'];

        $associationMetadata = $this->objectMetadataFactory->createAndAddAssociationMetadata(
            $entityMetadata,
            $entityClass,
            $config,
            $fieldName,
            $field,
            $targetAction,
            $targetClass
        );

        $targetConfig = $field->getTargetEntity();
        $targetEntityMetadata = $this->objectMetadataFactory->createObjectMetadata($targetClass, $targetConfig);
        $associationMetadata->setTargetMetadata($targetEntityMetadata);

        return $associationMetadata;
    }

    /**
     * @param EntityDefinitionConfig      $parentConfig
     * @param string                      $parentClassName
     * @param string                      $parentFieldName
     * @param string                      $targetFieldName
     * @param EntityDefinitionFieldConfig $targetField
     *
     * @return EntityDefinitionFieldConfig
     *
     * @throws RuntimeException if the linked field cannot be found or it has invalid configuration
     */
    public function getLinkedField(
        EntityDefinitionConfig $parentConfig,
        $parentClassName,
        $parentFieldName,
        $targetFieldName,
        EntityDefinitionFieldConfig $targetField
    ) {
        $linkedField = $parentConfig->getField($targetField->getPropertyPath($targetFieldName));
        if (null === $linkedField) {
            throw new RuntimeException(sprintf(
                'The "%s" property path is not supported for the nested object.'
                . ' Parent Field: %s::%s. Target Field: %s.',
                $targetField->getPropertyPath($targetFieldName),
                $parentClassName,
                $parentFieldName,
                $targetFieldName
            ));
        }
        if ($linkedField->hasTargetEntity()) {
            throw new RuntimeException(sprintf(
                'An association is not supported for the nested object.'
                . ' Parent Field: %s::%s. Target Field: %s.',
                $parentClassName,
                $parentFieldName,
                $targetFieldName
            ));
        }

        return $linkedField;
    }

    /**
     * @param PropertyMetadata            $propertyMetadata
     * @param string                      $fieldName
     * @param EntityDefinitionFieldConfig $field
     * @param string                      $targetAction
     */
    public function setTargetPropertyPath(
        PropertyMetadata $propertyMetadata,
        $fieldName,
        EntityDefinitionFieldConfig $field,
        $targetAction
    ) {
        $targetPropertyPath = $this->metadataHelper->getFormPropertyPath($field, $targetAction);
        if ($targetPropertyPath !== $fieldName) {
            $propertyMetadata->setPropertyPath($targetPropertyPath);
        }
    }
}
