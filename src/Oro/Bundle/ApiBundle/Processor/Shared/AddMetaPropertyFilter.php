<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Oro\Bundle\ApiBundle\Filter\MetaPropertyFilter;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Adds "meta" filter.
 * This filter can be used to specify which entity meta properties should be returned.
 */
class AddMetaPropertyFilter implements ProcessorInterface
{
    const FILTER_KEY = 'meta';

    const FILTER_DESCRIPTION = 'A list of meta properties to be returned. Comma-separated names.';

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var Context $context */

        $filters = $context->getFilters();
        if ($filters->has(self::FILTER_KEY)) {
            // the "meta" filter is already added
            return;
        }

        /**
         * TODO: BAP-9470 - Refactoring of filters in API to add possibility to add dependency between filters
         *
         * this filter has descriptive nature and it should be added to the list of filters
         * only if descriptions are requested
         * actually a filtering by this filter is performed by
         * @see \Oro\Bundle\ApiBundle\Processor\Shared\JsonApi\HandleIncludeFilter
         */
        /*
        if (!$context->hasConfigExtra(DescriptionsConfigExtra::NAME)) {
            return;
        }
        */

        if (!$context->getConfig()->isMetaPropertiesEnabled()) {
            // the "meta" filter is disabled
            return;
        }
        $filter = new MetaPropertyFilter(DataType::STRING, self::FILTER_DESCRIPTION);
        $filter->setArrayAllowed(true);
        $filters->add(self::FILTER_KEY, $filter);
    }
}
