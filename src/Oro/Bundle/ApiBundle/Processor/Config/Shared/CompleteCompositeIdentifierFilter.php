<?php

namespace Oro\Bundle\ApiBundle\Processor\Config\Shared;

use Oro\Bundle\ApiBundle\Processor\Config\ConfigContext;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Adds a filter by identifier for entities with composite identifier.
 */
class CompleteCompositeIdentifierFilter implements ProcessorInterface
{
    const IDENTIFIER_FILTER_NAME = 'id';

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var ConfigContext $context */

        if (count($context->getResult()->getIdentifierFieldNames()) <= 1) {
            // not composite identifier
            return;
        }

        $filters = $context->getFilters();
        if ($filters->hasField(self::IDENTIFIER_FILTER_NAME)) {
            // the filter for composite identifier was already added
            return;
        }

        $filter = $filters->addField(self::IDENTIFIER_FILTER_NAME);
        $filter->setType('composite_identifier');
        $filter->setDataType(DataType::STRING);
        $filter->setArrayAllowed(true);
    }
}
