<?php

namespace Oro\Bundle\ApiBundle\Processor\Config\Shared;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Processor\Config\ConfigContext;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\EntityBundle\Provider\ExclusionProviderInterface;

/**
 * Sets "identifier only" configuration for all associations were not configured yet.
 * Marks all not accessible associations as excluded.
 * The entity exclusion provider is used.
 */
class CompleteDefinitionOfAssociations implements ProcessorInterface
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
        $this->doctrineHelper    = $doctrineHelper;
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

        $this->completeAssociations($definition, $entityClass);
    }

    /**
     * @param EntityDefinitionConfig $definition
     * @param string                 $entityClass
     */
    protected function completeAssociations(EntityDefinitionConfig $definition, $entityClass)
    {
        $metadata     = $this->doctrineHelper->getEntityMetadataForClass($entityClass);
        $associations = $metadata->getAssociationMappings();
        foreach ($associations as $propertyPath => $mapping) {
            $fieldName = $definition->findFieldNameByPropertyPath($propertyPath);
            $field = $fieldName
                ? $definition->getField($fieldName)
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
