<?php

namespace Oro\Bundle\ApiBundle\Processor\Config\Shared;

use Doctrine\ORM\Mapping\ClassMetadata;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Bundle\ApiBundle\Config\EntityConfigInterface;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\FiltersConfig;
use Oro\Bundle\ApiBundle\Processor\Config\ConfigContext;

/**
 * Makes sure that the filters configuration contains all supported filters
 * and all filters are fully configured.
 */
class CompleteFilters extends CompleteSection
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var ConfigContext $context */

        $this->complete($context->getFilters(), $context->getClassName(), $context->getResult());
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

        /** @var FiltersConfig $section */
        $this->completeFieldFilters($section, $metadata, $definition);
        $this->completeAssociationFilters($section, $metadata, $definition);
    }

    /**
     * @param FiltersConfig          $filters
     * @param ClassMetadata          $metadata
     * @param EntityDefinitionConfig $definition
     */
    protected function completeFieldFilters(
        FiltersConfig $filters,
        ClassMetadata $metadata,
        EntityDefinitionConfig $definition
    ) {
        $indexedFields = $this->doctrineHelper->getIndexedFields($metadata);
        foreach ($indexedFields as $propertyPath => $dataType) {
            $fieldName = $definition->findFieldNameByPropertyPath($propertyPath);
            if ($fieldName) {
                $filter = $filters->getOrAddField($fieldName);
                if (!$filter->hasDataType()) {
                    $filter->setDataType($dataType);
                }
                if (!$filter->hasArrayAllowed()) {
                    $filter->setArrayAllowed();
                }
            }
        }
    }

    /**
     * @param FiltersConfig          $filters
     * @param ClassMetadata          $metadata
     * @param EntityDefinitionConfig $definition
     */
    protected function completeAssociationFilters(
        FiltersConfig $filters,
        ClassMetadata $metadata,
        EntityDefinitionConfig $definition
    ) {
        $relations = $this->doctrineHelper->getIndexedAssociations($metadata);
        foreach ($relations as $propertyPath => $dataType) {
            $fieldName = $definition->findFieldNameByPropertyPath($propertyPath);
            if ($fieldName) {
                $filter = $filters->getOrAddField($fieldName);
                if (!$filter->hasDataType()) {
                    $filter->setDataType($dataType);
                }
                if (!$filter->hasArrayAllowed()) {
                    $filter->setArrayAllowed();
                }
            }
        }
    }
}
