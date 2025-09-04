<?php

namespace Oro\Bundle\SearchBundle\Api\Filter;

use Doctrine\Common\Collections\Expr\ExpressionVisitor;
use Oro\Bundle\SearchBundle\Provider\AbstractSearchMappingProvider;

/**
 * The factory to create SearchQueryFilter.
 */
class SearchQueryFilterFactory
{
    public function __construct(
        private readonly AbstractSearchMappingProvider $searchMappingProvider,
        private readonly SearchFieldResolverFactoryInterface $searchFieldResolverFactory,
        private readonly ?ExpressionVisitor $searchQueryCriteriaVisitor = null
    ) {
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
