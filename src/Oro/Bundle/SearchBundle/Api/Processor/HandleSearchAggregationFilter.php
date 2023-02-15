<?php

namespace Oro\Bundle\SearchBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\SearchBundle\Api\Filter\SearchAggregationFilter;
use Oro\Bundle\SearchBundle\Query\SearchQueryInterface;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Checks whether a search aggregation filter exists,
 * and if so, applies it to a search query from the context.
 */
class HandleSearchAggregationFilter implements ProcessorInterface
{
    private string $aggregationFilterName;

    public function __construct(string $aggregationFilterName = 'aggregations')
    {
        $this->aggregationFilterName = $aggregationFilterName;
    }

    /**
     * {@inheritDoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var Context $context */

        $query = $context->getQuery();
        if (!$query instanceof SearchQueryInterface) {
            return;
        }

        $filter = $context->getFilters()->get($this->aggregationFilterName);
        if (!$filter instanceof SearchAggregationFilter) {
            return;
        }

        $filter->applyToSearchQuery($query);
    }
}
