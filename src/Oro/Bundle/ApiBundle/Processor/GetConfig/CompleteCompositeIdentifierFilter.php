<?php

namespace Oro\Bundle\ApiBundle\Processor\GetConfig;

use Oro\Bundle\ApiBundle\Config\FiltersConfig;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Adds filters by identifier for entities and associations with composite identifier.
 */
class CompleteCompositeIdentifierFilter implements ProcessorInterface
{
    private const IDENTIFIER_FILTER_NAME = 'id';

    /**
     * {@inheritDoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var ConfigContext $context */

        $filters = $context->getFilters();
        if (null === $filters) {
            return;
        }

        if (!$filters->hasField(self::IDENTIFIER_FILTER_NAME)
            && \count($context->getResult()->getIdentifierFieldNames()) > 1
        ) {
            $this->addCompositeIdentifierFilter($filters, self::IDENTIFIER_FILTER_NAME, 'composite_identifier');
        }

        $fieldsConfig = $context->getResult()->getFields();
        foreach ($fieldsConfig as $fieldName => $fieldConfig) {
            if (!$fieldConfig->isExcluded() && !$filters->hasField($fieldName)) {
                $targetDefinition = $fieldConfig->getTargetEntity();
                if (null !== $targetDefinition && \count($targetDefinition?->getIdentifierFieldNames()) > 1) {
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
