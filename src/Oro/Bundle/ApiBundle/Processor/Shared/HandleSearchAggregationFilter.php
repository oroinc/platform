<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Oro\Bundle\ApiBundle\Filter\SearchAggregationFilter;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\SearchBundle\Query\SearchQueryInterface;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Checks whether a search aggregation filter exists,
 * and if so, applies it to a search query from the context.
 */
class HandleSearchAggregationFilter implements ProcessorInterface
{
    /** @var string */
    private $aggregationFilterName;

    public function __construct(string $aggregationFilterName = 'aggregations')
    {
        $this->aggregationFilterName = $aggregationFilterName;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
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
