<?php

namespace Oro\Bundle\ApiBundle\Processor\Config\Shared;

use Doctrine\ORM\Mapping\ClassMetadata;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfigExtra;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionFieldConfig;
use Oro\Bundle\ApiBundle\Config\FilterIdentifierFieldsConfigExtra;
use Oro\Bundle\ApiBundle\Model\EntityIdentifier;
use Oro\Bundle\ApiBundle\Processor\Config\ConfigContext;
use Oro\Bundle\ApiBundle\Provider\ConfigProvider;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\EntityBundle\Provider\ExclusionProviderInterface;
use Oro\Bundle\EntityExtendBundle\Entity\Manager\AssociationManager;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType;

/**
 * Makes sure that identifier field names are set for ORM entities.
 * Updates configuration to ask the EntitySerializer that the entity class should be returned
 * together with related entity data in case if the entity implemented using Doctrine table inheritance.
 * Completes configuration if extended associations (associations with data_type=association:...[:...]).
 * If "identifier_fields_only" config extra is not exist:
 * * Adds fields and associations which were not configured yet based on an entity metadata.
 * * Marks all not accessible fields and associations as excluded.
 * * The entity exclusion provider is used.
 * * Sets "identifier only" configuration for all associations which were not configured yet.
 * If "identifier_fields_only" config extra exists:
 * * Adds identifier fields which were not configured yet based on an entity metadata.
 * * Removes all other fields and association.
 * By performance reasons all these actions are done in one processor.
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class CompleteDefinition implements ProcessorInterface
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var ExclusionProviderInterface */
    protected $exclusionProvider;

    /** @var ConfigProvider */
    protected $configProvider;

    /** @var AssociationManager */
    protected $associationManager;

    /**
     * @param DoctrineHelper             $doctrineHelper
     * @param ExclusionProviderInterface $exclusionProvider
     * @param ConfigProvider             $configProvider
     * @param AssociationManager         $associationManager
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        ExclusionProviderInterface $exclusionProvider,
        ConfigProvider $configProvider,
        AssociationManager $associationManager
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->exclusionProvider = $exclusionProvider;
        $this->configProvider = $configProvider;
        $this->associationManager = $associationManager;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var ConfigContext $context */

        $definition = $context->getResult();
        if ($definition->isExcludeAll()) {
            // already processed
            return;
        }

        $entityClass = $context->getClassName();
        if ($this->doctrineHelper->isManageableEntityClass($entityClass)) {
            $existingFields = $this->getExistingFields($definition);
            $metadata = $this->doctrineHelper->getEntityMetadataForClass($entityClass);
            if ($context->hasExtra(FilterIdentifierFieldsConfigExtra::NAME)) {
                $this->completeIdentifierFields($definition, $metadata, $existingFields);
            } else {
                $this->completeExtendedAssociations(
                    $definition,
                    $metadata->name,
                    $context->getVersion(),
                    $context->getRequestType()
                );
                $this->completeFields($definition, $metadata, $existingFields);
                $this->completeAssociations(
                    $definition,
                    $metadata,
                    $existingFields,
                    $context->getVersion(),
                    $context->getRequestType()
                );
            }
            // make sure that identifier field names are set
            $idFieldNames = $definition->getIdentifierFieldNames();
            if (empty($idFieldNames)) {
                $this->setIdentifierFieldNames($definition, $metadata);
            }
            // make sure "class name" meta field is added for entity with table inheritance
            if ($metadata->inheritanceType !== ClassMetadata::INHERITANCE_TYPE_NONE) {
                $this->addClassNameField($definition);
            }
        } else {
            if ($context->hasExtra(FilterIdentifierFieldsConfigExtra::NAME)) {
                $this->removeObjectNonIdentifierFields($definition);
            } else {
                $this->completeObjectAssociations($definition, $context->getVersion(), $context->getRequestType());
            }
        }
    }

    /**
     * @param EntityDefinitionConfig $definition
     *
     * @return array [property path => field name, ...]
     */
    protected function getExistingFields(EntityDefinitionConfig $definition)
    {
        $existingFields = [];
        $fields = $definition->getFields();
        foreach ($fields as $fieldName => $field) {
            $propertyPath = $field->getPropertyPath() ?: $fieldName;
            $existingFields[$propertyPath] = $fieldName;
        }

        return $existingFields;
    }

    /**
     * @param EntityDefinitionConfig $definition
     * @param ClassMetadata          $metadata
     */
    protected function setIdentifierFieldNames(EntityDefinitionConfig $definition, ClassMetadata $metadata)
    {
        $idFieldNames = [];
        $propertyPaths = $metadata->getIdentifierFieldNames();
        foreach ($propertyPaths as $propertyPath) {
            $idFieldNames[] = $definition->findFieldNameByPropertyPath($propertyPath) ?: $propertyPath;
        }
        $definition->setIdentifierFieldNames($idFieldNames);
    }

    /**
     * @param EntityDefinitionConfig $definition
     */
    protected function addClassNameField(EntityDefinitionConfig $definition)
    {
        $classNameField = $definition->findFieldNameByPropertyPath(ConfigUtil::CLASS_NAME);
        if (null === $classNameField) {
            $classNameField = $definition->addField(ConfigUtil::CLASS_NAME);
            $classNameField->setMetaProperty(true);
            $classNameField->setDataType(DataType::STRING);
        }
    }

    /**
     * @param EntityDefinitionConfig $definition
     * @param ClassMetadata          $metadata
     * @param array                  $existingFields [property path => field name, ...]
     */
    protected function completeIdentifierFields(
        EntityDefinitionConfig $definition,
        ClassMetadata $metadata,
        array $existingFields
    ) {
        $idFieldNames = $metadata->getIdentifierFieldNames();
        // remove all not identifier fields
        foreach ($existingFields as $propertyPath => $fieldName) {
            if (!in_array($propertyPath, $idFieldNames, true) && !ConfigUtil::isMetadataProperty($propertyPath)) {
                $definition->removeField($fieldName);
            }
        }
        // make sure all identifier fields are added
        foreach ($idFieldNames as $propertyPath) {
            if (!isset($existingFields[$propertyPath])) {
                $definition->addField($propertyPath);
            }
        }
    }

    /**
     * @param EntityDefinitionConfig $definition
     * @param string                 $entityClass
     * @param string                 $version
     * @param RequestType            $requestType
     */
    protected function completeExtendedAssociations(
        EntityDefinitionConfig $definition,
        $entityClass,
        $version,
        RequestType $requestType
    ) {
        if ($definition->hasFields()) {
            $fields = $definition->getFields();
            foreach ($fields as $fieldName => $field) {
                $dataType = $field->getDataType();
                if ($dataType && 0 === strpos($dataType, 'association:')) {
                    list(, $associationType, $associationKind) = array_pad(explode(':', $dataType, 3), 3, null);
                    $targetClass = $field->getTargetClass();
                    if (!$targetClass) {
                        $targetClass = EntityIdentifier::class;
                        $field->setTargetClass($targetClass);
                    }
                    if (!$field->getTargetType()) {
                        $field->setTargetType($this->getExtendedAssociationTargetType($associationType));
                    }
                    if (!$field->getDependsOn()) {
                        $field->setDependsOn(
                            $this->getExtendedAssociationTargetFields(
                                $entityClass,
                                $associationType,
                                $associationKind
                            )
                        );
                    }
                    $this->completeAssociation($field, $targetClass, $version, $requestType);
                }
            }
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

        return $isCollection ? 'to-many' : 'to-one';
    }

    /**
     * @param string $entityClass
     * @param string $associationType
     * @param string $associationKind
     *
     * @return string[]
     */
    protected function getExtendedAssociationTargetFields($entityClass, $associationType, $associationKind)
    {
        $targets = $this->associationManager->getAssociationTargets(
            $entityClass,
            null,
            $associationType,
            $associationKind
        );

        return array_values($targets);
    }

    /**
     * @param EntityDefinitionConfig $definition
     * @param ClassMetadata          $metadata
     * @param array                  $existingFields [property path => field name, ...]
     */
    protected function completeFields(
        EntityDefinitionConfig $definition,
        ClassMetadata $metadata,
        array $existingFields
    ) {
        $fieldNames = $metadata->getFieldNames();
        foreach ($fieldNames as $propertyPath) {
            $field = isset($existingFields[$propertyPath])
                ? $definition->getField($existingFields[$propertyPath])
                : $definition->addField($propertyPath);
            if (!$field->hasExcluded()
                && !$field->isExcluded()
                && $this->exclusionProvider->isIgnoredField($metadata, $propertyPath)
            ) {
                $field->setExcluded();
            }
        }
    }

    /**
     * @param EntityDefinitionConfig $definition
     * @param ClassMetadata          $metadata
     * @param array                  $existingFields [property path => field name, ...]
     * @param string                 $version
     * @param RequestType            $requestType
     */
    protected function completeAssociations(
        EntityDefinitionConfig $definition,
        ClassMetadata $metadata,
        array $existingFields,
        $version,
        RequestType $requestType
    ) {
        $associations = $metadata->getAssociationMappings();
        foreach ($associations as $propertyPath => $mapping) {
            $field = isset($existingFields[$propertyPath])
                ? $definition->getField($existingFields[$propertyPath])
                : $definition->addField($propertyPath);
            if (!$field->hasExcluded()
                && !$field->isExcluded()
                && $this->exclusionProvider->isIgnoredRelation($metadata, $propertyPath)
            ) {
                $field->setExcluded();
            }
            $this->completeAssociation($field, $mapping['targetEntity'], $version, $requestType);
        }
    }

    /**
     * @param EntityDefinitionFieldConfig $field
     * @param string                 $targetClass
     * @param string                 $version
     * @param RequestType            $requestType
     */
    protected function completeAssociation(
        EntityDefinitionFieldConfig $field,
        $targetClass,
        $version,
        RequestType $requestType
    ) {
        $targetEntity = $field->getTargetEntity();
        if ($targetEntity && $targetEntity->isExcludeAll()) {
            return;
        }

        $config = $this->configProvider->getConfig(
            $targetClass,
            $version,
            $requestType,
            [new EntityDefinitionConfigExtra(), new FilterIdentifierFieldsConfigExtra()]
        );
        if ($config->hasDefinition()) {
            if (!$field->getTargetClass()) {
                $field->setTargetClass($targetClass);
            }
            if (!$targetEntity) {
                $targetEntity = $field->createAndSetTargetEntity();
            }
            $targetEntity->setExcludeAll();
            $targetDefinition = $config->getDefinition();
            $targetFields = $targetDefinition->getFields();
            foreach ($targetFields as $targetFieldName => $targetField) {
                $targetEntity->addField($targetFieldName, $targetField);
            }
            $targetEntity->setIdentifierFieldNames($targetDefinition->getIdentifierFieldNames());
            $field->setCollapsed();
        }
    }

    /**
     * @param EntityDefinitionConfig $definition
     */
    protected function removeObjectNonIdentifierFields(EntityDefinitionConfig $definition)
    {
        $idFieldNames = $definition->getIdentifierFieldNames();
        $fieldNames = array_keys($definition->getFields());
        foreach ($fieldNames as $fieldName) {
            if (!in_array($fieldName, $idFieldNames, true) && !ConfigUtil::isMetadataProperty($fieldName)) {
                $definition->removeField($fieldName);
            }
        }
    }

    /**
     * @param EntityDefinitionConfig $definition
     * @param string                 $version
     * @param RequestType            $requestType
     */
    protected function completeObjectAssociations(
        EntityDefinitionConfig $definition,
        $version,
        RequestType $requestType
    ) {
        $fields = $definition->getFields();
        foreach ($fields as $fieldName => $field) {
            $targetClass = $field->getTargetClass();
            if (!$targetClass) {
                continue;
            }
            $this->completeAssociation($field, $targetClass, $version, $requestType);
        }
    }
}
