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

    /** @var ExpressionVisitor|null */
    private $searchQueryCriteriaVisitor;

    /**
     * @param AbstractSearchMappingProvider $searchMappingProvider
     * @param ExpressionVisitor|null        $searchQueryCriteriaVisitor
     */
    public function __construct(
        AbstractSearchMappingProvider $searchMappingProvider,
        ExpressionVisitor $searchQueryCriteriaVisitor = null
    ) {
        $this->searchMappingProvider = $searchMappingProvider;
        $this->searchQueryCriteriaVisitor = $searchQueryCriteriaVisitor;
    }

    /**
     * Creates a new instance of SearchQueryFilter.
     *
     * @param string $dataType
     *
     * @return SearchQueryFilter
     */
    public function createFilter($dataType)
    {
        $filter = new SearchQueryFilter($dataType);
        $filter->setSearchMappingProvider($this->searchMappingProvider);
        $filter->setSearchQueryCriteriaVisitor($this->searchQueryCriteriaVisitor);

        return $filter;
    }
}
