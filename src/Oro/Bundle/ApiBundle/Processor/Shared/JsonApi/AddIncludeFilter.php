<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared\JsonApi;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Config\DescriptionsConfigExtra;
use Oro\Bundle\ApiBundle\Filter\IncludeFilter;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Request\DataType;

/**
 * Adds "include" filter.
 * This filter can be used to specify which related entities should be returned.
 */
class AddIncludeFilter implements ProcessorInterface
{
    const FILTER_KEY = 'include';

    const FILTER_DESCRIPTION =
        'A list of related entities to be included. Comma-separated paths, e.g. \'comments,comments.author\'.';

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var Context $context */

        $filters = $context->getFilters();
        if ($filters->has(self::FILTER_KEY)) {
            // the "include" filter is already added
            return;
        }

        /**
         * TODO: BAP-9470 - Refactoring of filters in API to add possibility to add dependency between filters
         *
         * this filter has descriptive nature and it should be added to the list of filters
         * only if descriptions are requested
         * actually a filtering by this filter is performed by
         * @see Oro\Bundle\ApiBundle\Processor\Shared\JsonApi\HandleIncludeFilter
         */
        /*
        if (!$context->hasConfigExtra(DescriptionsConfigExtra::NAME)) {
            return;
        }
        */

        if (!$context->getConfig()->isInclusionEnabled()) {
            // the "include" filter is disabled
            return;
        }
        $associations = $context->getMetadata()->getAssociations();
        if (empty($associations)) {
            // the "include" filter has sense only if an entity has at least one association
            return;
        }

        $filter = new IncludeFilter(DataType::STRING, self::FILTER_DESCRIPTION);
        $filter->setArrayAllowed(true);
        $filters->add(self::FILTER_KEY, $filter);
    }
}
