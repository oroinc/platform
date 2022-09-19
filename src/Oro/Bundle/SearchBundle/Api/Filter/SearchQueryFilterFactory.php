<?php

namespace Oro\Bundle\SearchBundle\Api\Filter;

use Doctrine\Common\Collections\Expr\ExpressionVisitor;
use Oro\Bundle\SearchBundle\Provider\AbstractSearchMappingProvider;

/**
 * The factory to create SearchQueryFilter.
 */
class SearchQueryFilterFactory
{
    private AbstractSearchMappingProvider $searchMappingProvider;
    private SearchFieldResolverFactory $searchFieldResolverFactory;
    private ?ExpressionVisitor $searchQueryCriteriaVisitor;

    public function __construct(
        AbstractSearchMappingProvider $searchMappingProvider,
        SearchFieldResolverFactory $searchFieldResolverFactory,
        ExpressionVisitor $searchQueryCriteriaVisitor = null
    ) {
        $this->searchMappingProvider = $searchMappingProvider;
        $this->searchFieldResolverFactory = $searchFieldResolverFactory;
        $this->searchQueryCriteriaVisitor = $searchQueryCriteriaVisitor;
    }

    /**
     * Creates a new instance of SearchQueryFilter.
     */
    public function createFilter(string $dataType): SearchQueryFilter
    {
        $filter = new SearchQueryFilter($dataType);
        $filter->setSearchMappingProvider($this->searchMappingProvider);
        $filter->setSearchFieldResolverFactory($this->searchFieldResolverFactory);
        if (null !== $this->searchQueryCriteriaVisitor) {
            $filter->setSearchQueryCriteriaVisitor($this->searchQueryCriteriaVisitor);
        }

        return $filter;
    }
}
