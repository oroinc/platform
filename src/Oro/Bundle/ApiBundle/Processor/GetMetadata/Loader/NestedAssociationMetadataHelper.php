<?php

namespace Oro\Bundle\ApiBundle\Processor\GetMetadata\Loader;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionFieldConfig;
use Oro\Bundle\ApiBundle\Exception\RuntimeException;
use Oro\Bundle\ApiBundle\Metadata\AssociationMetadata;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Metadata\PropertyMetadata;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType;

/**
 * Provides methods to build metadata for nested associations.
 */
class NestedAssociationMetadataHelper
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
     * @param string                      $fieldName
     * @param EntityDefinitionFieldConfig $field
     * @param string                      $targetAction
     *
     * @return AssociationMetadata
     *
     * @throws RuntimeException if nested association has invalid configuration
     */
    public function addNestedAssociation(
        EntityMetadata $entityMetadata,
        $entityClass,
        $fieldName,
        EntityDefinitionFieldConfig $field,
        $targetAction
    ) {
        $targetConfig = $field->getTargetEntity();
        $this->assertRequiredTargetField($targetConfig, ConfigUtil::CLASS_NAME, $entityClass, $fieldName);
        $idField = $this->assertRequiredTargetField(
            $targetConfig,
            $this->getIdentifierFieldName(),
            $entityClass,
            $fieldName
        );

        $targetClass = $field->getTargetClass();

        $associationMetadata = $entityMetadata->addAssociation(new AssociationMetadata($fieldName));
        $this->metadataHelper->setPropertyPath($associationMetadata, $fieldName, $field, $targetAction);
        $idFieldDataType = $idField->getDataType();
        if ($idFieldDataType) {
            $associationMetadata->setDataType($idFieldDataType);
        }
        $associationMetadata->setIsNullable(true);
        $associationMetadata->setCollapsed(true);
        $associationMetadata->setAssociationType(RelationType::MANY_TO_ONE);
        $associationMetadata->setTargetClassName($targetClass);

        $targetEntityMetadata = $this->objectMetadataFactory->createObjectMetadata($targetClass, $targetConfig);
        $targetEntityMetadata->setInheritedType(true);
        $associationMetadata->setTargetMetadata($targetEntityMetadata);

        return $associationMetadata;
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

    /**
     * @return string
     */
    public function getIdentifierFieldName()
    {
        return 'id';
    }

    /**
     * @param EntityDefinitionConfig $targetConfig
     * @param string                 $targetFieldName
     * @param string                 $parentClassName
     * @param string                 $parentFieldName
     *
     * @return EntityDefinitionFieldConfig
     *
     * @throws RuntimeException if the target field cannot be found or it has invalid configuration
     */
    private function assertRequiredTargetField(
        EntityDefinitionConfig $targetConfig,
        $targetFieldName,
        $parentClassName,
        $parentFieldName
    ) {
        $targetField = $targetConfig->getField($targetFieldName);
        if (null === $targetField) {
            throw new RuntimeException(sprintf(
                'The "%s" field should be configured for the nested association.'
                . ' Parent Field: %s::%s.',
                $targetFieldName,
                $parentClassName,
                $parentFieldName
            ));
        }
        if (!$targetField->hasPropertyPath()) {
            throw new RuntimeException(sprintf(
                'A property path should be configured for the "%s" field.'
                . ' Parent Field: %s::%s.',
                $targetFieldName,
                $parentClassName,
                $parentFieldName
            ));
        }
        if ($targetField->hasTargetEntity()) {
            throw new RuntimeException(sprintf(
                'The "%s" field should not be an association.'
                . ' Parent Field: %s::%s.',
                $targetFieldName,
                $parentClassName,
                $parentFieldName
            ));
        }

        return $targetField;
    }
}
