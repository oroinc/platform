<?php

namespace Oro\Bundle\SearchBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\SearchBundle\Api\Filter\SearchAggregationFilter;
use Oro\Bundle\SearchBundle\Api\Model\LoadEntityIdsBySearchQuery;
use Oro\Bundle\SearchBundle\Query\Query as SearchQuery;
use Oro\Bundle\SearchBundle\Query\SearchQueryInterface;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Checks whether a search aggregation filter exists,
 * and if so, applies it to a search query from the context.
 */
class HandleSearchAggregationFilter implements ProcessorInterface
{
    public const OPERATION_NAME = 'handle_search_aggregation_filter';

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

        if ($context->isProcessed(self::OPERATION_NAME)) {
            // a aggregation filter was already applied to a search query
            return;
        }

        $query = $context->getQuery();
        if ($query instanceof SearchQueryInterface) {
            $query = $query->getQuery();
        } elseif ($query instanceof LoadEntityIdsBySearchQuery) {
            $query = $query->getSearchQuery();
        }
        if (!$query instanceof SearchQuery) {
            return;
        }

        $filter = $context->getFilters()->get($this->aggregationFilterName);
        if (!$filter instanceof SearchAggregationFilter) {
            return;
        }

        $filter->applyToSearchQuery($query);
        $context->set(NormalizeSearchAggregatedData::AGGREGATION_DATA_TYPES, $filter->getAggregationDataTypes());
        $context->setProcessed(self::OPERATION_NAME);
    }
}
