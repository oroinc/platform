<?php

namespace Oro\Bundle\ApiBundle\Processor\Config\Shared;

use Doctrine\ORM\Mapping\ClassMetadata;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\FilterIdentifierFieldsConfigExtra;
use Oro\Bundle\ApiBundle\Processor\Config\ConfigContext;
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
 */
class CompleteDefinition implements ProcessorInterface
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var ExclusionProviderInterface */
    protected $exclusionProvider;

    /**
     * @param DoctrineHelper             $doctrineHelper
     * @param ExclusionProviderInterface $exclusionProvider
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        ExclusionProviderInterface $exclusionProvider
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->exclusionProvider = $exclusionProvider;
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
        if (!$this->doctrineHelper->isManageableEntityClass($entityClass)) {
            // only manageable entities are supported
            return;
        }

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
                $definition->remove($fieldName);
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
}
