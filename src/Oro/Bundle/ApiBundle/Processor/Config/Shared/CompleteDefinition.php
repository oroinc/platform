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
use Oro\Bundle\EntityExtendBundle\Extend\FieldTypeHelper;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

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
 * Updates configuration of fields if other fields a linked to them using "property_path".
 * Sets "exclusion_policy = all" for the entity. It means that the configuration
 * of all fields and associations was completed.
 * Completes configuration of fields that represent nested objects.
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

    /** @var FieldTypeHelper */
    protected $fieldTypeHelper;

    /**
     * @param DoctrineHelper             $doctrineHelper
     * @param ExclusionProviderInterface $exclusionProvider
     * @param ConfigProvider             $configProvider
     * @param AssociationManager         $associationManager
     * @param FieldTypeHelper            $fieldTypeHelper
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        ExclusionProviderInterface $exclusionProvider,
        ConfigProvider $configProvider,
        AssociationManager $associationManager,
        FieldTypeHelper $fieldTypeHelper
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->exclusionProvider = $exclusionProvider;
        $this->configProvider = $configProvider;
        $this->associationManager = $associationManager;
        $this->fieldTypeHelper = $fieldTypeHelper;
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
                $version = $context->getVersion();
                $requestType = $context->getRequestType();
                $this->completeExtendedAssociations($definition, $metadata->name, $version, $requestType);
                $this->completeFields($definition, $metadata, $existingFields);
                $this->completeAssociations($definition, $metadata, $existingFields, $version, $requestType);
                $this->completeDependentAssociations($definition, $metadata, $version, $requestType);
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

        // mark the entity configuration as processed
        $definition->setExcludeAll();
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
            $propertyPath = $field->getPropertyPath();
            if (empty($propertyPath) || ConfigUtil::IGNORE_PROPERTY_PATH === $propertyPath) {
                $propertyPath = $fieldName;
            }
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
                if (DataType::isNestedObject($dataType)) {
                    $this->completeNestedObject($fieldName, $field);
                } elseif (DataType::isExtendedAssociation($dataType)) {
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

                    list($associationType, $associationKind) = DataType::parseExtendedAssociation($dataType);
                    $targetClass = $field->getTargetClass();
                    if (!$targetClass) {
                        $targetClass = EntityIdentifier::class;
                        $field->setTargetClass($targetClass);
                    }
                    $field->setTargetType($this->getExtendedAssociationTargetType($associationType));

                    $this->completeAssociation($field, $targetClass, $version, $requestType);

                    $targets = $this->getExtendedAssociationTargets($entityClass, $associationType, $associationKind);
                    $field->setDependsOn(array_values($targets));
                    $this->fixExtendedAssociationIdentifierDataType($field, array_keys($targets));
                } elseif (DataType::isExtendedInverseAssociation($dataType)) {
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

                    list($associationSourceClass, $associationType, $associationKind)
                        = DataType::parseExtendedInverseAssociation($dataType);
                    $field->setTargetClass($associationSourceClass);
                    $reverseType = ExtendHelper::getReverseRelationType(
                        $this->fieldTypeHelper->getUnderlyingType($associationType)
                    );
                    $field->setTargetType($this->getExtendedAssociationTargetType($reverseType));

                    // inverse association fields should be excluded to avoid this fields in main actions
                    $field->setExcluded(true);

                    $this->completeAssociation($field, $associationSourceClass, $version, $requestType);
                    $targets = $this->getExtendedAssociationTargets(
                        $associationSourceClass,
                        $associationType,
                        $associationKind
                    );
                    $field->set('association-field', $targets[$entityClass]);
                    $field->set('association-kind', $associationKind);
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
     * @return array [target_entity_class => field_name]
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
        $config = $this->configProvider->getConfig(
            $targetClass,
            $version,
            $requestType,
            [new EntityDefinitionConfigExtra(), new FilterIdentifierFieldsConfigExtra()]
        );
        if ($config->hasDefinition()) {
            $targetDefinition = $config->getDefinition();

            if (!$field->getTargetClass()) {
                $field->setTargetClass($targetClass);
            }

            $targetEntity = $field->getTargetEntity();
            $isExcludeAll = $targetEntity && $targetEntity->isExcludeAll();
            if (!$targetEntity) {
                $targetEntity = $field->createAndSetTargetEntity();
            }
            $targetEntity->setIdentifierFieldNames($targetDefinition->getIdentifierFieldNames());
            if (!$isExcludeAll) {
                $targetEntity->setExcludeAll();
                $targetFields = $targetDefinition->getFields();
                foreach ($targetFields as $targetFieldName => $targetField) {
                    $targetEntity->addField($targetFieldName, $targetField);
                }
                $field->setCollapsed();
            }
        }
    }

    /**
     * @param EntityDefinitionConfig $definition
     * @param ClassMetadata          $metadata
     * @param string                 $version
     * @param RequestType            $requestType
     */
    protected function completeDependentAssociations(
        EntityDefinitionConfig $definition,
        ClassMetadata $metadata,
        $version,
        RequestType $requestType
    ) {
        $fields = $definition->getFields();
        foreach ($fields as $field) {
            $propertyPath = $field->getPropertyPath();
            if (!$propertyPath) {
                continue;
            }
            $path = ConfigUtil::explodePropertyPath($propertyPath);
            if (1 === count($path)) {
                continue;
            }

            $targetDefinition = $definition;
            $targetMetadata = $metadata;
            foreach ($path as $targetFieldName) {
                $isAssociation = $targetMetadata->hasAssociation($targetFieldName);
                $targetField = $targetDefinition->getField($targetFieldName);
                if (null === $targetField) {
                    $targetField = $targetDefinition->addField($targetFieldName);
                    $targetField->setExcluded();
                    if ($isAssociation) {
                        $this->completeAssociation(
                            $targetField,
                            $targetMetadata->getAssociationTargetClass($targetFieldName),
                            $version,
                            $requestType
                        );
                    }
                }
                if (!$isAssociation) {
                    break;
                }
                $targetDefinition = $targetField->getOrCreateTargetEntity();
                $targetMetadata = $this->doctrineHelper->getEntityMetadataForClass(
                    $targetMetadata->getAssociationTargetClass($targetFieldName)
                );
            }
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
            if (DataType::isNestedObject($field->getDataType())) {
                $this->completeNestedObject($fieldName, $field);
            } else {
                $targetClass = $field->getTargetClass();
                if (!$targetClass) {
                    continue;
                }
                $this->completeAssociation($field, $targetClass, $version, $requestType);
            }
        }
    }

    /**
     * @param string                      $fieldName
     * @param EntityDefinitionFieldConfig $field
     */
    protected function completeNestedObject($fieldName, EntityDefinitionFieldConfig $field)
    {
        $field->setPropertyPath(ConfigUtil::IGNORE_PROPERTY_PATH);

        $target = $field->getOrCreateTargetEntity();
        $target->setExcludeAll();

        $dependsOn = $field->getDependsOn();
        if (null === $dependsOn) {
            $dependsOn = [];
        }
        $targetFields = $target->getFields();
        foreach ($targetFields as $targetFieldName => $targetField) {
            $targetPropertyPath = $targetField->getPropertyPath($targetFieldName);
            if (!in_array($targetPropertyPath, $dependsOn, true)) {
                $dependsOn[] = $targetPropertyPath;
            }
        }
        $field->setDependsOn($dependsOn);

        $formOptions = $field->getFormOptions();
        if (null === $formOptions || !array_key_exists('property_path', $formOptions)) {
            $formOptions['property_path'] = $fieldName;
            $field->setFormOptions($formOptions);
        }
    }
}
