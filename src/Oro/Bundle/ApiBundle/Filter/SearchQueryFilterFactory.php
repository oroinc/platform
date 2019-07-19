<?php

namespace Oro\Bundle\ApiBundle\Filter;

use Doctrine\Common\Collections\Expr\ExpressionVisitor;
use Oro\Bundle\SearchBundle\Provider\AbstractSearchMappingProvider;

/**
 * The factory to create SearchQueryFilter.
 */
class SearchQueryFilterFactory
{
    /** @var AbstractSearchMappingProvider */
    private $searchMappingProvider;

    /** @var SearchFieldResolverFactory */
    private $searchFieldResolverFactory;

    /** @var ExpressionVisitor|null */
    private $searchQueryCriteriaVisitor;

    /**
     * @param AbstractSearchMappingProvider $searchMappingProvider
     * @param SearchFieldResolverFactory    $searchFieldResolverFactory
     * @param ExpressionVisitor|null        $searchQueryCriteriaVisitor
     */
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
     *
     * @param string $dataType
     *
     * @return SearchQueryFilter
     */
    public function createFilter(string $dataType): SearchQueryFilter
    {
        $filter = new SearchQueryFilter($dataType);
        $filter->setSearchMappingProvider($this->searchMappingProvider);
        $filter->setSearchFieldResolverFactory($this->searchFieldResolverFactory);
        $filter->setSearchQueryCriteriaVisitor($this->searchQueryCriteriaVisitor);

        return $filter;
    }
}
