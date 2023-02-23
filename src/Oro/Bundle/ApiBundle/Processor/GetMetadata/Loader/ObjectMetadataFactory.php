<?php

namespace Oro\Bundle\ApiBundle\Processor\GetMetadata\Loader;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionFieldConfig;
use Oro\Bundle\ApiBundle\Metadata\AssociationMetadata;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Metadata\FieldMetadata;
use Oro\Bundle\ApiBundle\Metadata\MetaPropertyMetadata;
use Oro\Bundle\ApiBundle\Model\EntityIdentifier;
use Oro\Bundle\ApiBundle\Provider\ExtendedAssociationProvider;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType;

/**
 * The metadata factory for non manageable entities.
 */
class ObjectMetadataFactory
{
    private MetadataHelper $metadataHelper;
    private ExtendedAssociationProvider $extendedAssociationProvider;

    public function __construct(
        MetadataHelper $metadataHelper,
        ExtendedAssociationProvider $extendedAssociationProvider
    ) {
        $this->metadataHelper = $metadataHelper;
        $this->extendedAssociationProvider = $extendedAssociationProvider;
    }

    public function createObjectMetadata(string $entityClass, EntityDefinitionConfig $config): EntityMetadata
    {
        $entityMetadata = new EntityMetadata($entityClass);
        $entityMetadata->setIdentifierFieldNames($config->getIdentifierFieldNames());
        if (is_a($entityClass, EntityIdentifier::class, true)) {
            $entityMetadata->setInheritedType(true);
        }

        return $entityMetadata;
    }

    public function createAndAddMetaPropertyMetadata(
        EntityMetadata $entityMetadata,
        string $entityClass,
        string $fieldName,
        EntityDefinitionFieldConfig $field,
        ?string $targetAction
    ): MetaPropertyMetadata {
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

    public function createAndAddFieldMetadata(
        EntityMetadata $entityMetadata,
        string $entityClass,
        string $fieldName,
        EntityDefinitionFieldConfig $field,
        ?string $targetAction
    ): FieldMetadata {
        $fieldMetadata = $entityMetadata->addField(new FieldMetadata($fieldName));
        $this->metadataHelper->setPropertyPath($fieldMetadata, $fieldName, $field, $targetAction);
        $fieldMetadata->setDataType(
            $this->metadataHelper->assertDataType($field->getDataType(), $entityClass, $fieldName)
        );
        $fieldMetadata->setIsNullable(
            !\in_array($fieldName, $entityMetadata->getIdentifierFieldNames(), true)
        );

        return $fieldMetadata;
    }

    public function createAndAddAssociationMetadata(
        EntityMetadata $entityMetadata,
        string $entityClass,
        EntityDefinitionConfig $config,
        string $fieldName,
        EntityDefinitionFieldConfig $field,
        ?string $targetAction,
        string $targetClass = null
    ): AssociationMetadata {
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

    private function completeExtendedAssociationMetadata(
        AssociationMetadata $associationMetadata,
        string $entityClass,
        EntityDefinitionConfig $config,
        EntityDefinitionFieldConfig $field
    ): void {
        [$associationType, $associationKind] = DataType::parseExtendedAssociation($field->getDataType());
        $this->setAssociationDataType($associationMetadata, $field);
        $associationMetadata->setAssociationType($associationType);
        $associationTargets = null;
        $targetFieldNames = $field->getDependsOn();
        if ($targetFieldNames) {
            $associationTargets = $this->extendedAssociationProvider->filterExtendedAssociationTargets(
                $this->getAssociationOwnerClass($entityClass, $config, $field),
                $associationType,
                $associationKind,
                $targetFieldNames
            );
        }
        if ($associationTargets) {
            $associationMetadata->setAcceptableTargetClassNames(array_keys($associationTargets));
        } else {
            $associationMetadata->setEmptyAcceptableTargetsAllowed(false);
        }
        $associationMetadata->setIsCollection($field->isCollectionValuedAssociation());
    }

    private function getAssociationOwnerClass(
        string $entityClass,
        EntityDefinitionConfig $config,
        EntityDefinitionFieldConfig $field
    ): string {
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

    private function getAssociationDataType(EntityDefinitionFieldConfig $field): ?string
    {
        $associationDataType = null;
        $targetEntity = $field->getTargetEntity();
        if ($targetEntity) {
            $associationDataType = DataType::STRING;
            $targetIdFieldNames = $targetEntity->getIdentifierFieldNames();
            if (1 === \count($targetIdFieldNames)) {
                $targetIdField = $targetEntity->getField(reset($targetIdFieldNames));
                if ($targetIdField) {
                    $associationDataType = $targetIdField->getDataType();
                }
            }
        }

        return $associationDataType;
    }

    private function setAssociationDataType(
        AssociationMetadata $associationMetadata,
        EntityDefinitionFieldConfig $field
    ): void {
        $associationDataType = $this->getAssociationDataType($field);
        if ($associationDataType) {
            $associationMetadata->setDataType($associationDataType);
        }
    }

    private function setAssociationType(AssociationMetadata $associationMetadata, bool $isCollection): void
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
