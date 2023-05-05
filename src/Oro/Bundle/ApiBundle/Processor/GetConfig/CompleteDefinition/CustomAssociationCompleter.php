<?php

namespace Oro\Bundle\ApiBundle\Processor\GetConfig\CompleteDefinition;

use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionFieldConfig;
use Oro\Bundle\ApiBundle\Model\EntityIdentifier;
use Oro\Bundle\ApiBundle\Provider\ExtendedAssociationProvider;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType;

/**
 * Completes the configuration of different kind of custom associations.
 * @see \Oro\Bundle\ApiBundle\Request\DataType::isNestedObject
 * @see \Oro\Bundle\ApiBundle\Request\DataType::isNestedAssociation
 * @see \Oro\Bundle\ApiBundle\Request\DataType::isExtendedAssociation
 */
class CustomAssociationCompleter implements CustomDataTypeCompleterInterface
{
    private DoctrineHelper $doctrineHelper;
    private CompleteAssociationHelper $associationHelper;
    private ExtendedAssociationProvider $extendedAssociationProvider;

    public function __construct(
        DoctrineHelper $doctrineHelper,
        CompleteAssociationHelper $associationHelper,
        ExtendedAssociationProvider $extendedAssociationProvider
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->associationHelper = $associationHelper;
        $this->extendedAssociationProvider = $extendedAssociationProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function completeCustomDataType(
        ClassMetadata $metadata,
        EntityDefinitionConfig $definition,
        string $fieldName,
        EntityDefinitionFieldConfig $field,
        string $dataType,
        string $version,
        RequestType $requestType
    ): bool {
        $result = false;
        if (DataType::isNestedObject($dataType)) {
            $this->associationHelper->completeNestedObject($fieldName, $field);
            $result = true;
        } elseif (DataType::isNestedAssociation($dataType)) {
            $this->associationHelper->completeNestedAssociation($definition, $field, $version, $requestType);
            $result = true;
        } elseif (DataType::isExtendedAssociation($dataType)) {
            $this->completeExtendedAssociation($metadata->name, $fieldName, $field, $version, $requestType);
            $result = true;
        }

        return $result;
    }

    private function completeExtendedAssociation(
        string $entityClass,
        string $fieldName,
        EntityDefinitionFieldConfig $field,
        string $version,
        RequestType $requestType
    ): void {
        if ($field->hasTargetType()) {
            throw new \RuntimeException(sprintf(
                'The "target_type" option cannot be configured for "%s::%s".',
                $entityClass,
                $fieldName
            ));
        }
        if ($field->getDependsOn()) {
            throw new \RuntimeException(sprintf(
                'The "depends_on" option cannot be configured for "%s::%s".',
                $entityClass,
                $fieldName
            ));
        }

        [$associationType, $associationKind] = DataType::parseExtendedAssociation($field->getDataType());
        $targetClass = $field->getTargetClass();
        if (!$targetClass) {
            $targetClass = EntityIdentifier::class;
            $field->setTargetClass($targetClass);
        }
        $field->setTargetType($this->getExtendedAssociationTargetType($associationType));

        $this->associationHelper->completeAssociation($field, $targetClass, $version, $requestType);

        $associationTargets = $this->extendedAssociationProvider->getExtendedAssociationTargets(
            $entityClass,
            $associationType,
            $associationKind,
            $version,
            $requestType
        );
        if ($associationTargets) {
            $field->setDependsOn(array_values($associationTargets));
            $this->fixExtendedAssociationIdentifierDataType($field, array_keys($associationTargets));
        } else {
            $field->setFormOption('mapped', false);
        }
    }

    private function getExtendedAssociationTargetType(string $associationType): string
    {
        $isCollection =
            \in_array($associationType, RelationType::$toManyRelations, true)
            || RelationType::MULTIPLE_MANY_TO_ONE === $associationType;

        return $this->associationHelper->getAssociationTargetType($isCollection);
    }

    private function fixExtendedAssociationIdentifierDataType(
        EntityDefinitionFieldConfig $field,
        array $targetEntityClasses
    ): void {
        $targetEntity = $field->getTargetEntity();
        if (null === $targetEntity) {
            return;
        }
        $idFieldNames = $targetEntity->getIdentifierFieldNames();
        if (1 !== \count($idFieldNames)) {
            return;
        }
        $idField = $targetEntity->getField(reset($idFieldNames));
        if (null === $idField) {
            return;
        }

        if (DataType::STRING === $idField->getDataType()) {
            $idDataType = $this->getIdDataType($targetEntityClasses);
            if ($idDataType) {
                $idField->setDataType($idDataType);
            }
        }
    }

    private function getIdDataType(array $targetEntityClasses): ?string
    {
        $idDataType = null;
        foreach ($targetEntityClasses as $targetEntityClass) {
            $targetMetadata = $this->doctrineHelper->getEntityMetadataForClass($targetEntityClass);
            $targetIdFieldNames = $targetMetadata->getIdentifierFieldNames();
            if (1 !== \count($targetIdFieldNames)) {
                $idDataType = null;
                break;
            }
            $dataType = $targetMetadata->getTypeOfField(reset($targetIdFieldNames));
            if (null === $idDataType) {
                $idDataType = $dataType;
            } elseif ($idDataType !== $dataType) {
                $idDataType = null;
                break;
            }
        }

        return $idDataType;
    }
}
