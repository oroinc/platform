<?php

namespace Oro\Bundle\ApiBundle\Processor\GetMetadata\Loader;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionFieldConfig;
use Oro\Bundle\ApiBundle\Metadata\AssociationMetadata;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Metadata\FieldMetadata;
use Oro\Bundle\ApiBundle\Metadata\MetaPropertyMetadata;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Bundle\EntityExtendBundle\Entity\Manager\AssociationManager;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType;

/**
 * The metadata factory for non manageable entities.
 */
class ObjectMetadataFactory
{
    /** @var MetadataHelper */
    protected $metadataHelper;

    /** @var AssociationManager */
    protected $associationManager;

    /**
     * @param MetadataHelper     $metadataHelper
     * @param AssociationManager $associationManager
     */
    public function __construct(
        MetadataHelper $metadataHelper,
        AssociationManager $associationManager
    ) {
        $this->metadataHelper = $metadataHelper;
        $this->associationManager = $associationManager;
    }

    /**
     * @param string                 $entityClass
     * @param EntityDefinitionConfig $config
     *
     * @return EntityMetadata
     */
    public function createObjectMetadata($entityClass, EntityDefinitionConfig $config)
    {
        $entityMetadata = new EntityMetadata();
        $entityMetadata->setClassName($entityClass);
        $entityMetadata->setIdentifierFieldNames($config->getIdentifierFieldNames());

        return $entityMetadata;
    }

    /**
     * @param EntityMetadata              $entityMetadata
     * @param string                      $entityClass
     * @param string                      $fieldName
     * @param EntityDefinitionFieldConfig $field
     * @param string                      $targetAction
     *
     * @return MetaPropertyMetadata
     */
    public function createAndAddMetaPropertyMetadata(
        EntityMetadata $entityMetadata,
        $entityClass,
        $fieldName,
        EntityDefinitionFieldConfig $field,
        $targetAction
    ) {
        $metaPropertyMetadata = $entityMetadata->addMetaProperty(new MetaPropertyMetadata($fieldName));
        $this->metadataHelper->setPropertyPath($metaPropertyMetadata, $fieldName, $field, $targetAction);
        $metaPropertyMetadata->setDataType(
            $this->metadataHelper->assertDataType($field->getDataType(), $entityClass, $fieldName)
        );
        $resultName = $field->getMetaPropertyResultName();
        if ($resultName) {
            $metaPropertyMetadata->setResultName($resultName);
        }

        return $metaPropertyMetadata;
    }

    /**
     * @param EntityMetadata              $entityMetadata
     * @param string                      $entityClass
     * @param string                      $fieldName
     * @param EntityDefinitionFieldConfig $field
     * @param string                      $targetAction
     *
     * @return FieldMetadata
     */
    public function createAndAddFieldMetadata(
        EntityMetadata $entityMetadata,
        $entityClass,
        $fieldName,
        EntityDefinitionFieldConfig $field,
        $targetAction
    ) {
        $fieldMetadata = $entityMetadata->addField(new FieldMetadata($fieldName));
        $this->metadataHelper->setPropertyPath($fieldMetadata, $fieldName, $field, $targetAction);
        $fieldMetadata->setDataType(
            $this->metadataHelper->assertDataType($field->getDataType(), $entityClass, $fieldName)
        );
        $fieldMetadata->setIsNullable(
            !in_array($fieldName, $entityMetadata->getIdentifierFieldNames(), true)
        );

        return $fieldMetadata;
    }

    /**
     * @param EntityMetadata              $entityMetadata
     * @param string                      $entityClass
     * @param EntityDefinitionConfig      $config
     * @param string                      $fieldName
     * @param EntityDefinitionFieldConfig $field
     * @param string                      $targetAction
     * @param string|null                 $targetClass
     *
     * @return AssociationMetadata
     */
    public function createAndAddAssociationMetadata(
        EntityMetadata $entityMetadata,
        $entityClass,
        EntityDefinitionConfig $config,
        $fieldName,
        EntityDefinitionFieldConfig $field,
        $targetAction,
        $targetClass = null
    ) {
        if (!$targetClass) {
            $targetClass = $field->getTargetClass();
        }
        $associationMetadata = $entityMetadata->addAssociation(new AssociationMetadata($fieldName));
        $this->metadataHelper->setPropertyPath($associationMetadata, $fieldName, $field, $targetAction);
        $associationMetadata->setTargetClassName($targetClass);
        $associationMetadata->setIsNullable(true);
        $associationMetadata->setCollapsed($field->isCollapsed());

        $completed = false;

        $dataType = $field->getDataType();
        if (!$dataType) {
            $dataType = $this->getAssociationDataType($field);
        } elseif (DataType::isExtendedAssociation($dataType)) {
            $this->completeExtendedAssociationMetadata($associationMetadata, $entityClass, $config, $field);
            $completed = true;
        }

        if (!$completed) {
            $associationMetadata->setDataType($dataType);
            $this->setAssociationType($associationMetadata, $field->isCollectionValuedAssociation());
            $associationMetadata->addAcceptableTargetClassName($targetClass);
        }

        return $associationMetadata;
    }

    /**
     * @param AssociationMetadata         $associationMetadata
     * @param string                      $entityClass
     * @param EntityDefinitionConfig      $config
     * @param EntityDefinitionFieldConfig $field
     */
    protected function completeExtendedAssociationMetadata(
        AssociationMetadata $associationMetadata,
        $entityClass,
        EntityDefinitionConfig $config,
        EntityDefinitionFieldConfig $field
    ) {
        list($associationType, $associationKind) = DataType::parseExtendedAssociation($field->getDataType());
        $this->setAssociationDataType($associationMetadata, $field);
        $associationMetadata->setAssociationType($associationType);
        $targets = $this->getExtendedAssociationTargets(
            $this->getAssociationOwnerClass($entityClass, $config, $field),
            $associationType,
            $associationKind
        );
        if (empty($targets)) {
            $associationMetadata->setEmptyAcceptableTargetsAllowed(false);
        } else {
            $associationMetadata->setAcceptableTargetClassNames(array_keys($targets));
        }
        $associationMetadata->setIsCollection((bool)$field->isCollectionValuedAssociation());
    }

    /**
     * @param string                      $entityClass
     * @param EntityDefinitionConfig      $config
     * @param EntityDefinitionFieldConfig $field
     *
     * @return null|string
     */
    protected function getAssociationOwnerClass(
        $entityClass,
        EntityDefinitionConfig $config,
        EntityDefinitionFieldConfig $field
    ) {
        $propertyPath = $field->getPropertyPath();
        if (!$propertyPath) {
            return $entityClass;
        }

        $lastDelimiter = strrpos($propertyPath, ConfigUtil::PATH_DELIMITER);
        if (false === $lastDelimiter) {
            return $entityClass;
        }

        $ownerField = $config->findFieldByPath(substr($propertyPath, 0, $lastDelimiter), true);
        if (null === $ownerField) {
            return $entityClass;
        }

        $associationOwnerClass = $ownerField->getTargetClass();
        if (!$associationOwnerClass) {
            return $entityClass;
        }

        return $associationOwnerClass;
    }

    /**
     * @param string $entityClass
     * @param string $associationType
     * @param string $associationKind
     *
     * @return array [class name => field name, ...]
     */
    protected function getExtendedAssociationTargets($entityClass, $associationType, $associationKind)
    {
        return $this->associationManager->getAssociationTargets(
            $entityClass,
            null,
            $associationType,
            $associationKind
        );
    }

    /**
     * @param EntityDefinitionFieldConfig $field
     *
     * @return string|null
     */
    protected function getAssociationDataType(EntityDefinitionFieldConfig $field)
    {
        $associationDataType = null;
        $targetEntity = $field->getTargetEntity();
        if ($targetEntity) {
            $associationDataType = DataType::STRING;
            $targetIdFieldNames = $targetEntity->getIdentifierFieldNames();
            if (1 === count($targetIdFieldNames)) {
                $targetIdField = $targetEntity->getField(reset($targetIdFieldNames));
                if ($targetIdField) {
                    $associationDataType = $targetIdField->getDataType();
                }
            }
        }

        return $associationDataType;
    }

    /**
     * @param AssociationMetadata         $associationMetadata
     * @param EntityDefinitionFieldConfig $field
     */
    protected function setAssociationDataType(
        AssociationMetadata $associationMetadata,
        EntityDefinitionFieldConfig $field
    ) {
        $associationDataType = $this->getAssociationDataType($field);
        if ($associationDataType) {
            $associationMetadata->setDataType($associationDataType);
        }
    }

    /**
     * @param AssociationMetadata $associationMetadata
     * @param bool                $isCollection
     */
    protected function setAssociationType(AssociationMetadata $associationMetadata, $isCollection)
    {
        if ($isCollection) {
            $associationMetadata->setAssociationType(RelationType::MANY_TO_MANY);
            $associationMetadata->setIsCollection(true);
        } else {
            $associationMetadata->setAssociationType(RelationType::MANY_TO_ONE);
            $associationMetadata->setIsCollection(false);
        }
    }
}
