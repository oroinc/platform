<?php

namespace Oro\Bundle\ApiBundle\Processor\Config\Shared;

use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Bundle\ApiBundle\Config\EntityConfigInterface;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Processor\Config\ConfigContext;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;

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
        $entityClass,
        EntityDefinitionConfig $definition
    ) {
        $metadata = $this->doctrineHelper->getEntityMetadataForClass($entityClass);

        $this->completeSorters(
            $section,
            $definition,
            $definition->getIdentifierFieldNames()
        );
        $this->completeSortersByPropertyPath(
            $section,
            $definition,
            array_keys($this->doctrineHelper->getIndexedFields($metadata))
        );
        $this->completeSortersForAssociations($section, $metadata, $definition);
    }

    /**
     * @param EntityConfigInterface  $section
     * @param EntityDefinitionConfig $definition
     * @param string[]               $fields
     */
    protected function completeSorters(
        EntityConfigInterface $section,
        EntityDefinitionConfig $definition,
        array $fields
    ) {
        foreach ($fields as $fieldName) {
            if ($definition->hasField($fieldName)) {
                $section->getOrAddField($fieldName);
            }
        }
    }

    /**
     * @param EntityConfigInterface  $section
     * @param EntityDefinitionConfig $definition
     * @param string[]               $fields
     */
    protected function completeSortersByPropertyPath(
        EntityConfigInterface $section,
        EntityDefinitionConfig $definition,
        array $fields
    ) {
        foreach ($fields as $propertyPath) {
            $fieldName = $definition->findFieldNameByPropertyPath($propertyPath);
            if ($fieldName && !$section->hasField($fieldName)) {
                $section->addField($fieldName);
            }
        }
    }

    /**
     * @param EntityConfigInterface  $section
     * @param ClassMetadata          $metadata
     * @param EntityDefinitionConfig $definition
     */
    protected function completeSortersForAssociations(
        EntityConfigInterface $section,
        ClassMetadata $metadata,
        EntityDefinitionConfig $definition
    ) {
        $fields = array_keys($this->doctrineHelper->getIndexedAssociations($metadata));
        foreach ($fields as $propertyPath) {
            $sorter = $section->findField($propertyPath, true);
            $fieldName = $definition->findFieldNameByPropertyPath($propertyPath);
            if ($fieldName && (null !== $sorter || !$section->hasField($fieldName))) {
                if (null === $sorter) {
                    $sorter = $section->addField($fieldName);
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
