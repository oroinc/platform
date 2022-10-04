<?php

namespace Oro\Bundle\ApiBundle\Processor\GetConfig;

use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Adds a filter by identifier for entities with association composite identifier.
 */
class CompleteAssociationCompositeIdFilter implements ProcessorInterface
{
    public const ASSOC_COMPOSITE_IDENTIFIER_TYPE = 'association_composite_identifier';

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var ConfigContext $context */
        $filters = $context->getFilters();
        if (!$filters) {
            return;
        }

        // for associated mapping fields that has composite id.
        $fieldsConfig = $context->getResult()->getFields();
        foreach ($fieldsConfig as $filterKey => $fieldConfig) {
            if ($fieldConfig->isExcluded()) {
                continue;
            }

            $targetFieldsConfig = $fieldConfig->getTargetEntity();
            if ($targetFieldsConfig && count($targetFieldsConfig?->getIdentifierFieldNames()) > 1) {
                // will force to override existing filter.
                $filter = $filters->addField($filterKey);
                if ($filter) {
                    $filter->setType(self::ASSOC_COMPOSITE_IDENTIFIER_TYPE);
                    $filter->setDataType(DataType::STRING);
                    $filter->setArrayAllowed(true);
                }
            }
        }
    }
}
