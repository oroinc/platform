<?php

namespace Oro\Bundle\ApiBundle\Processor\Config\Shared;

use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\ApiBundle\Config\EntityConfigInterface;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Processor\Config\ConfigContext;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Component\ChainProcessor\ContextInterface;

/**
 * Makes sure that the sorters configuration contains all supported sorters
 * and all sorters are fully configured.
 */
class CompleteSorters extends CompleteSection
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var ConfigContext $context */

        $this->complete($context->getSorters(), $context->getClassName(), $context->getResult());
    }

    /**
     * {@inheritdoc}
     */
    protected function completeFields(
        EntityConfigInterface $section,
        string $entityClass,
        EntityDefinitionConfig $definition
    ) {
        $metadata = $this->doctrineHelper->getEntityMetadataForClass($entityClass);

        $this->completeSortersForIdentifierFields($section, $definition);
        $this->completeSortersForFields($section, $metadata, $definition);
        $this->completeSortersForAssociations($section, $metadata, $definition);
    }

    /**
     * @param EntityConfigInterface  $sorters
     * @param EntityDefinitionConfig $definition
     */
    protected function completeSortersForIdentifierFields(
        EntityConfigInterface $sorters,
        EntityDefinitionConfig $definition
    ) {
        $idFieldNames = $definition->getIdentifierFieldNames();
        foreach ($idFieldNames as $fieldName) {
            if ($definition->hasField($fieldName)) {
                $sorters->getOrAddField($fieldName);
            }
        }
    }

    /**
     * @param EntityConfigInterface  $sorters
     * @param ClassMetadata          $metadata
     * @param EntityDefinitionConfig $definition
     */
    protected function completeSortersForFields(
        EntityConfigInterface $sorters,
        ClassMetadata $metadata,
        EntityDefinitionConfig $definition
    ) {
        $indexedFields = $this->doctrineHelper->getIndexedFields($metadata);
        foreach ($indexedFields as $propertyPath => $dataType) {
            $fieldName = $definition->findFieldNameByPropertyPath($propertyPath);
            if ($fieldName && !$sorters->hasField($fieldName)) {
                $sorters->addField($fieldName);
            }
        }
    }

    /**
     * @param EntityConfigInterface  $sorters
     * @param ClassMetadata          $metadata
     * @param EntityDefinitionConfig $definition
     */
    protected function completeSortersForAssociations(
        EntityConfigInterface $sorters,
        ClassMetadata $metadata,
        EntityDefinitionConfig $definition
    ) {
        $indexedAssociations = $this->doctrineHelper->getIndexedAssociations($metadata);
        foreach ($indexedAssociations as $propertyPath => $dataType) {
            $sorter = $sorters->findField($propertyPath, true);
            $fieldName = $definition->findFieldNameByPropertyPath($propertyPath);
            if ($fieldName && (null !== $sorter || !$sorters->hasField($fieldName))) {
                if (null === $sorter) {
                    $sorter = $sorters->addField($fieldName);
                }
                if ($metadata->isCollectionValuedAssociation($propertyPath)) {
                    $targetIdIdFieldName = $this->doctrineHelper
                        ->getEntityMetadataForClass($metadata->getAssociationTargetClass($propertyPath))
                        ->getSingleIdentifierFieldName();
                    $sorter->setPropertyPath($propertyPath . ConfigUtil::PATH_DELIMITER . $targetIdIdFieldName);
                }
            }
        }
    }
}
