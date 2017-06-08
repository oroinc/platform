<?php

namespace Oro\Bundle\ApiBundle\Processor\Config\Shared\CompleteDefinition;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionFieldConfig;
use Oro\Bundle\ApiBundle\Model\EntityIdentifier;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\EntityExtendBundle\Entity\Manager\AssociationManager;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType;

class CompleteCustomAssociationHelper
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var CompleteAssociationHelper */
    protected $associationHelper;

    /** @var AssociationManager */
    protected $associationManager;

    /**
     * @param DoctrineHelper            $doctrineHelper
     * @param CompleteAssociationHelper $associationHelper
     * @param AssociationManager        $associationManager
     */
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
     * @param string                 $entityClass
     * @param EntityDefinitionConfig $definition
     * @param string                 $version
     * @param RequestType            $requestType
     */
    public function completeCustomAssociations(
        $entityClass,
        EntityDefinitionConfig $definition,
        $version,
        RequestType $requestType
    ) {
        $fields = $definition->getFields();
        foreach ($fields as $fieldName => $field) {
            $this->completeCustomAssociation($entityClass, $fieldName, $field, $version, $requestType);
        }
    }

    /**
     * @param string                      $entityClass
     * @param string                      $fieldName
     * @param EntityDefinitionFieldConfig $field
     * @param string                      $version
     * @param RequestType                 $requestType
     */
    protected function completeCustomAssociation(
        $entityClass,
        $fieldName,
        EntityDefinitionFieldConfig $field,
        $version,
        RequestType $requestType
    ) {
        $dataType = $field->getDataType();
        if ($dataType) {
            if (DataType::isNestedObject($dataType)) {
                $this->associationHelper->completeNestedObject($fieldName, $field);
            } elseif (DataType::isNestedAssociation($dataType)) {
                $this->associationHelper->completeNestedAssociation($field, $version, $requestType);
            } elseif (DataType::isExtendedAssociation($dataType)) {
                $this->completeExtendedAssociation($entityClass, $fieldName, $field, $version, $requestType);
            }
        }
    }

    /**
     * @param string                      $entityClass
     * @param string                      $fieldName
     * @param EntityDefinitionFieldConfig $field
     * @param string                      $version
     * @param RequestType                 $requestType
     */
    protected function completeExtendedAssociation(
        $entityClass,
        $fieldName,
        EntityDefinitionFieldConfig $field,
        $version,
        RequestType $requestType
    ) {
        if ($field->getTargetType()) {
            throw new \RuntimeException(
                sprintf(
                    'The "target_type" option cannot be configured for "%s::%s".',
                    $entityClass,
                    $fieldName
                )
            );
        }
        if ($field->getDependsOn()) {
            throw new \RuntimeException(
                sprintf(
                    'The "depends_on" option cannot be configured for "%s::%s".',
                    $entityClass,
                    $fieldName
                )
            );
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
    protected function getExtendedAssociationTargetType($associationType)
    {
        $isCollection =
            in_array($associationType, RelationType::$toManyRelations, true)
            || RelationType::MULTIPLE_MANY_TO_ONE === $associationType;

        return $this->associationHelper->getAssociationTargetType($isCollection);
    }

    /**
     * @param string $entityClass
     * @param string $associationType
     * @param string $associationKind
     *
     * @return array [target entity class => field name, ...]
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
     * @param string[]                    $targets
     */
    protected function fixExtendedAssociationIdentifierDataType(EntityDefinitionFieldConfig $field, array $targets)
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
