<?php

namespace Oro\Bundle\ApiBundle\Processor\GetConfig\CompleteDefinition;

use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionFieldConfig;
use Oro\Bundle\ApiBundle\Model\EntityIdentifier;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\EntityExtendBundle\Entity\Manager\AssociationManager;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType;

/**
 * Completes the configuration of different kind of custom associations.
 * @see \Oro\Bundle\ApiBundle\Request\DataType::isNestedObject
 * @see \Oro\Bundle\ApiBundle\Request\DataType::isNestedAssociation
 * @see \Oro\Bundle\ApiBundle\Request\DataType::isExtendedAssociation
 */
class CustomAssociationCompleter implements CustomDataTypeCompleterInterface
{
    /** @var DoctrineHelper */
    private $doctrineHelper;

    /** @var CompleteAssociationHelper */
    private $associationHelper;

    /** @var AssociationManager */
    private $associationManager;

    public function __construct(
        DoctrineHelper $doctrineHelper,
        CompleteAssociationHelper $associationHelper,
        AssociationManager $associationManager
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->associationHelper = $associationHelper;
        $this->associationManager = $associationManager;
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

    /**
     * @param string                      $entityClass
     * @param string                      $fieldName
     * @param EntityDefinitionFieldConfig $field
     * @param string                      $version
     * @param RequestType                 $requestType
     */
    private function completeExtendedAssociation(
        $entityClass,
        $fieldName,
        EntityDefinitionFieldConfig $field,
        $version,
        RequestType $requestType
    ) {
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

        list($associationType, $associationKind) = DataType::parseExtendedAssociation($field->getDataType());
        $targetClass = $field->getTargetClass();
        if (!$targetClass) {
            $targetClass = EntityIdentifier::class;
            $field->setTargetClass($targetClass);
        }
        $field->setTargetType($this->getExtendedAssociationTargetType($associationType));

        $this->associationHelper->completeAssociation($field, $targetClass, $version, $requestType);

        $targets = $this->getExtendedAssociationTargets($entityClass, $associationType, $associationKind);
        if (empty($targets)) {
            $field->setFormOption('mapped', false);
        } else {
            $field->setDependsOn(array_values($targets));
            $this->fixExtendedAssociationIdentifierDataType($field, array_keys($targets));
        }
    }

    /**
     * @param string $associationType
     *
     * @return string
     */
    private function getExtendedAssociationTargetType($associationType)
    {
        $isCollection =
            in_array($associationType, RelationType::$toManyRelations, true)
            || RelationType::MULTIPLE_MANY_TO_ONE === $associationType;

        return $this->associationHelper->getAssociationTargetType($isCollection);
    }

    /**
     * @param string      $entityClass
     * @param string      $associationType
     * @param string|null $associationKind
     *
     * @return array [target entity class => field name, ...]
     */
    private function getExtendedAssociationTargets($entityClass, $associationType, $associationKind)
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
     * @param string[]                    $targets
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function fixExtendedAssociationIdentifierDataType(EntityDefinitionFieldConfig $field, array $targets)
    {
        $targetEntity = $field->getTargetEntity();
        if (null === $targetEntity) {
            return;
        }
        $idFieldNames = $targetEntity->getIdentifierFieldNames();
        if (1 !== count($idFieldNames)) {
            return;
        }
        $idField = $targetEntity->getField(reset($idFieldNames));
        if (null === $idField) {
            return;
        }

        if (DataType::STRING === $idField->getDataType()) {
            $idDataType = null;
            foreach ($targets as $target) {
                $targetMetadata = $this->doctrineHelper->getEntityMetadataForClass($target);
                $targetIdFieldNames = $targetMetadata->getIdentifierFieldNames();
                if (1 !== count($targetIdFieldNames)) {
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
            if ($idDataType) {
                $idField->setDataType($idDataType);
            }
        }
    }
}
