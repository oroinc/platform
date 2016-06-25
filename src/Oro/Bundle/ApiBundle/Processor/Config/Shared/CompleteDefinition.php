<?php

namespace Oro\Bundle\ApiBundle\Processor\Config\Shared;

use Doctrine\ORM\Mapping\ClassMetadata;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfigExtra;
use Oro\Bundle\ApiBundle\Config\FilterIdentifierFieldsConfigExtra;
use Oro\Bundle\ApiBundle\Processor\Config\ConfigContext;
use Oro\Bundle\ApiBundle\Provider\ConfigProvider;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\EntityBundle\Provider\ExclusionProviderInterface;

/**
 * If "identifier_fields_only" config extra is not exist:
 * * Adds fields and associations which were not configured yet based on an entity metadata.
 * * Marks all not accessible fields and associations as excluded.
 * * The entity exclusion provider is used.
 * * Sets "identifier only" configuration for all associations which were not configured yet.
 * If "identifier_fields_only" config extra exists:
 * * Adds identifier fields which were not configured yet based on an entity metadata.
 * * Removes all other fields and association.
 * By performance reasons both actions are done in one processor.
 */
class CompleteDefinition implements ProcessorInterface
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var ExclusionProviderInterface */
    protected $exclusionProvider;

    /** @var ConfigProvider */
    protected $configProvider;

    /**
     * @param DoctrineHelper             $doctrineHelper
     * @param ExclusionProviderInterface $exclusionProvider
     * @param ConfigProvider             $configProvider
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        ExclusionProviderInterface $exclusionProvider,
        ConfigProvider $configProvider
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->exclusionProvider = $exclusionProvider;
        $this->configProvider = $configProvider;
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
            $existingFields = [];
            $fields = $definition->getFields();
            foreach ($fields as $fieldName => $field) {
                $propertyPath = $field->getPropertyPath() ?: $fieldName;
                $existingFields[$propertyPath] = $fieldName;
            }
            $metadata = $this->doctrineHelper->getEntityMetadataForClass($entityClass);
            if ($context->hasExtra(FilterIdentifierFieldsConfigExtra::NAME)) {
                $this->completeIdentifierFields($definition, $metadata, $existingFields);
            } else {
                $this->completeFields($definition, $metadata, $existingFields);
                $this->completeAssociations($definition, $metadata, $existingFields);
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
     * @param ClassMetadata          $metadata
     * @param array                  $existingFields [property path => field name, ...]
     */
    protected function completeIdentifierFields(
        EntityDefinitionConfig $definition,
        ClassMetadata $metadata,
        array $existingFields
    ) {
        // make sure all identifier fields are added
        $idFieldNames = $metadata->getIdentifierFieldNames();
        foreach ($idFieldNames as $propertyPath) {
            if (!isset($existingFields[$propertyPath])) {
                $definition->addField($propertyPath);
            }
        }
        // remove all not identifier fields
        foreach ($existingFields as $propertyPath => $fieldName) {
            if (!in_array($propertyPath, $idFieldNames, true)) {
                $definition->removeField($fieldName);
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
     */
    protected function completeAssociations(
        EntityDefinitionConfig $definition,
        ClassMetadata $metadata,
        array $existingFields
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

            if ($field->hasTargetEntity()) {
                $targetEntity = $field->getTargetEntity();
                if (!$targetEntity->isExcludeAll()) {
                    $targetEntity->setExcludeAll();
                }
            } else {
                $targetEntity = $field->createAndSetTargetEntity();
                $targetEntity->setExcludeAll();
                $idFieldNames = $this->doctrineHelper->getEntityIdentifierFieldNamesForClass($mapping['targetEntity']);
                foreach ($idFieldNames as $idFieldName) {
                    $targetEntity->addField($idFieldName);
                }
                $field->setCollapsed();
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
            if (!in_array($fieldName, $idFieldNames, true)) {
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

            if ($field->hasTargetEntity()) {
                $targetEntity = $field->getTargetEntity();
                if (!$targetEntity->isExcludeAll()) {
                    $targetEntity->setExcludeAll();
                }
            } else {
                $config = $this->configProvider->getConfig(
                    $targetClass,
                    $version,
                    $requestType,
                    [new EntityDefinitionConfigExtra(), new FilterIdentifierFieldsConfigExtra()]
                );
                if ($config->hasDefinition()) {
                    $targetEntity = $field->createAndSetTargetEntity();
                    $targetEntity->setExcludeAll();
                    $targetDefinition = $config->getDefinition();
                    $idFieldNames = $targetDefinition->getIdentifierFieldNames();
                    foreach ($idFieldNames as $idFieldName) {
                        $targetEntity->addField($idFieldName);
                    }
                    $field->setCollapsed();
                    if (!$field->getDataType() && 1 === count($idFieldNames)) {
                        $field->setDataType($targetDefinition->getField(reset($idFieldNames))->getDataType());
                    }
                }
            }
        }
    }
}
