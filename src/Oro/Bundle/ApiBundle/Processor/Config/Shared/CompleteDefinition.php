<?php

namespace Oro\Bundle\ApiBundle\Processor\Config\Shared;

use Doctrine\ORM\Mapping\ClassMetadata;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Processor\Config\ConfigContext;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\EntityBundle\Provider\ExclusionProviderInterface;

/**
 * Marks all not accessible fields and associations as excluded.
 * The entity exclusion provider is used.
 * Sets "identifier only" configuration for all associations were not configured yet.
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

        $this->completeDefinition($definition, $entityClass);
    }

    /**
     * @param EntityDefinitionConfig $definition
     * @param string                 $entityClass
     */
    protected function completeDefinition(EntityDefinitionConfig $definition, $entityClass)
    {
        $existingFields = [];
        $fields = $definition->getFields();
        foreach ($fields as $fieldName => $field) {
            $propertyPath = $field->getPropertyPath() ?: $fieldName;
            $existingFields[$propertyPath] = $fieldName;
        }
        $metadata = $this->doctrineHelper->getEntityMetadataForClass($entityClass);
        $this->completeFields($definition, $metadata, $existingFields);
        $this->completeAssociations($definition, $metadata, $existingFields);
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
