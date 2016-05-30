<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Filter\FilterInterface;
use Oro\Bundle\ApiBundle\Processor\Context;

/**
 * Applies all requested filters to the Criteria object.
 */
class BuildCriteria implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var Context $context */

        if ($context->hasQuery()) {
            // a query is already built
            return;
        }

        $criteria = $context->getCriteria();
        if (null === $criteria) {
            // the criteria object does not exist
            return;
        }

        $filterValues = $context->getFilterValues();
        $filters = $context->getFilters();
        /** @var FilterInterface $filter */
        foreach ($filters as $filterKey => $filter) {
            $filterValue = $filterValues->has($filterKey)
                ? $filterValues->get($filterKey)
                : null;
            $filter->apply($criteria, $filterValue);
        }
    }
}
