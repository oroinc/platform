<?php

namespace Oro\Bundle\SearchBundle\Datagrid\Filter\Adapter;

use Doctrine\Common\Collections\Expr\Expression;
use Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\SearchBundle\Query\SearchQueryInterface;

class SearchFilterDatasourceAdapter implements FilterDatasourceAdapterInterface
{
    /**
     * @var SearchQueryInterface
     */
    private $searchQuery;

    /**
     * @param SearchQueryInterface $query
     */
    public function __construct(SearchQueryInterface $query)
    {
        $this->searchQuery = $query;
    }

    /**
     * {@inheritdoc}
     */
    public function addRestriction($restriction, $condition, $isComputed = false)
    {
        if ($restriction instanceof Expression) {
            switch ($condition) {
                case FilterUtility::CONDITION_AND:
                    $this->searchQuery->getQuery()->getCriteria()->andWhere($restriction);
                    return;

                case FilterUtility::CONDITION_OR:
                    $this->searchQuery->getQuery()->getCriteria()->orWhere($restriction);
                    return;
            }
        }

        throw new \BadMethodCallException('Restriction not supported.');
    }

    /**
     * @return SearchQueryInterface
     */
    public function getSearchQuery()
    {
        return $this->searchQuery;
    }

    /**
     * @return Query
     */
    public function getWrappedSearchQuery()
    {
        if (!$this->searchQuery || !$this->searchQuery->getQuery()) {
            throw new \RuntimeException('Query not initialized properly');
        }

        return $this->searchQuery->getQuery();
    }

    /**
     * {@inheritdoc}
     */
    public function generateParameterName($filterName)
    {
        return $filterName;
    }

    /**
     * {@inheritdoc}
     */
    public function getDatabasePlatform()
    {
        throw new \BadMethodCallException('Method currently not supported.');
    }

    /**
     * {@inheritdoc}
     */
    public function groupBy($_)
    {
        throw new \BadMethodCallException('Method currently not supported.');
    }

    /**
     * {@inheritdoc}
     */
    public function addGroupBy($_)
    {
        throw new \BadMethodCallException('Method currently not supported.');
    }

    /**
     * {@inheritdoc}
     */
    public function expr()
    {
        throw new \BadMethodCallException('Use Criteria::expr() instead.');
    }

    /**
     * {@inheritdoc}
     */
    public function setParameter($key, $value, $type = null)
    {
        throw new \BadMethodCallException('Method currently not supported.');
    }

    /**
     * {@inheritdoc}
     */
    public function getFieldByAlias($fieldName)
    {
        throw new \BadMethodCallException('Method currently not supported.');
    }
}
