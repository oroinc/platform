<?php

namespace Oro\Bundle\ApiBundle\Processor\GetMetadata\Loader;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionFieldConfig;
use Oro\Bundle\ApiBundle\Exception\RuntimeException;
use Oro\Bundle\ApiBundle\Metadata\AssociationMetadata;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Metadata\PropertyMetadata;
use Oro\Bundle\ApiBundle\Model\FakeEntity;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;

/**
 * Provides methods to build metadata for nested objects.
 */
class NestedObjectMetadataHelper
{
    private MetadataHelper $metadataHelper;
    private ObjectMetadataFactory $objectMetadataFactory;

    public function __construct(
        MetadataHelper $metadataHelper,
        ObjectMetadataFactory $objectMetadataFactory
    ) {
        $this->metadataHelper = $metadataHelper;
        $this->objectMetadataFactory = $objectMetadataFactory;
    }

    public function addNestedObjectAssociation(
        EntityMetadata $entityMetadata,
        string $entityClass,
        EntityDefinitionConfig $config,
        string $fieldName,
        EntityDefinitionFieldConfig $field,
        ?string $targetAction
    ): AssociationMetadata {
        $formOptions = $field->getFormOptions();
        $inheritData = $formOptions['inherit_data'] ?? false;
        $targetClass = null;
        if (empty($formOptions['data_class'])) {
            if (!$inheritData) {
                throw new RuntimeException(sprintf(
                    'The "data_class" form option should be specified for the nested object'
                    . ' when the "inherit_data" form option is not specified. Field: %s::%s.',
                    $entityClass,
                    $fieldName
                ));
            }
        } elseif ($inheritData) {
            throw new RuntimeException(sprintf(
                'The "data_class" form option should not be specified for the nested object'
                . ' together with the "inherit_data" form option. Field: %s::%s.',
                $entityClass,
                $fieldName
            ));
        } else {
            $targetClass = $formOptions['data_class'];
        }
        if (!$targetClass) {
            $targetClass = FakeEntity::class;
        }

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
     * @throws RuntimeException if the linked field cannot be found or it has invalid configuration
     */
    public function getLinkedField(
        EntityDefinitionConfig $parentConfig,
        string $parentClassName,
        string $parentFieldName,
        string $targetFieldName,
        EntityDefinitionFieldConfig $targetField
    ): EntityDefinitionFieldConfig {
        $propertyPath = $targetField->getPropertyPath($targetFieldName);
        $linkedField = ConfigUtil::IGNORE_PROPERTY_PATH !== $propertyPath
            ? $parentConfig->findField($propertyPath, true)
            : $targetField;
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

    public function setTargetPropertyPath(
        PropertyMetadata $propertyMetadata,
        string $fieldName,
        EntityDefinitionFieldConfig $field,
        ?string $targetAction
    ): void {
        $targetPropertyPath = $this->metadataHelper->getFormPropertyPath($field, $targetAction);
        if ($targetPropertyPath !== $fieldName) {
            $propertyMetadata->setPropertyPath($targetPropertyPath);
        }
    }
}
