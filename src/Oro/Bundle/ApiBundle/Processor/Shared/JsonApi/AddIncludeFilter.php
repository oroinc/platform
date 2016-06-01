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

        if (!$context->hasConfigExtra(DescriptionsConfigExtra::NAME)) {
            /**
             * this filter has descriptive nature and it should be added to the list of filters
             * only if descriptions are requested
             * actually a filtering by this filter is performed by
             * @see Oro\Bundle\ApiBundle\Processor\Shared\JsonApi\HandleIncludeFilter
             */
            return;
        }

        $associations = $context->getMetadata()->getAssociations();
        if (empty($associations)) {
            // the "include" filter has sense only if an entity has at least one association
            return;
        }

        $filter = new IncludeFilter(
            DataType::STRING,
            'A list of related entities to be included. Comma-separated paths, e.g. \'comments,comments.author\'.'
        );
        $filter->setArrayAllowed(true);
        $filters->add(self::FILTER_KEY, $filter);
    }
}
