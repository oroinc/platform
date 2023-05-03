<?php

namespace Oro\Bundle\SearchBundle\Api\Filter;

/**
 * The factory to create SearchAggregationFilter.
 */
class SearchAggregationFilterFactory
{
    private SearchFieldResolverFactory $searchFieldResolverFactory;

    public function __construct(SearchFieldResolverFactory $searchFieldResolverFactory)
    {
        $this->searchFieldResolverFactory = $searchFieldResolverFactory;
    }

    /**
     * Creates a new instance of SearchAggregationFilter.
     */
    public function createFilter(string $dataType): SearchAggregationFilter
    {
        $filter = new SearchAggregationFilter($dataType);
        $filter->setSearchFieldResolverFactory($this->searchFieldResolverFactory);
        $filter->setArrayAllowed(true);

        return $filter;
    }
}
