<?php

namespace Oro\Bundle\ApiBundle\Processor\GetConfig;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\FiltersConfig;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Component\ChainProcessor\ContextInterface;

/**
 * Removes all filters marked as excluded.
 * Updates the property path attribute for existing filters.
 * Extracts filters from the definitions of related entities.
 * Removes filters by identifier field if they duplicate a filter by related entity.
 * For example if both "product" and "product.id" filters exist, the "product.id" filter will be removed.
 * Adds filters by identifier for entities and associations with composite identifier.
 */
class NormalizeFilters extends NormalizeSection
{
    private const IDENTIFIER_FILTER_NAME = 'id';

    /**
     * {@inheritDoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var ConfigContext $context */

        $definition = $context->getResult();
        $filters = $context->getFilters();
        $this->normalize($filters, ConfigUtil::FILTERS, $context->getClassName(), $definition);
        $this->completeCompositeIdentifierFilters($filters, $definition);
    }

    private function completeCompositeIdentifierFilters(
        FiltersConfig $filters,
        EntityDefinitionConfig $definition
    ): void {
        if (!$filters->hasField(self::IDENTIFIER_FILTER_NAME) && \count($definition->getIdentifierFieldNames()) > 1) {
            $this->addCompositeIdentifierFilter($filters, self::IDENTIFIER_FILTER_NAME, 'composite_identifier');
        }

        $fieldsConfig = $definition->getFields();
        foreach ($fieldsConfig as $fieldName => $fieldConfig) {
            if (!$fieldConfig->isExcluded() && !$filters->hasField($fieldName)) {
                $targetDefinition = $fieldConfig->getTargetEntity();
                if (null !== $targetDefinition && \count($targetDefinition->getIdentifierFieldNames()) > 1) {
                    $this->addCompositeIdentifierFilter($filters, $fieldName, 'association_composite_identifier');
                }
            }
        }
    }

    private function addCompositeIdentifierFilter(FiltersConfig $filters, string $name, string $ype): void
    {
        $filter = $filters->addField($name);
        $filter->setType($ype);
        $filter->setDataType(DataType::STRING);
        $filter->setArrayAllowed(true);
    }
}
