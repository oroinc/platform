<?php

namespace Oro\Bundle\SearchBundle\Datagrid\Datasource\Filter\Adapter;

use Doctrine\Common\Collections\Expr\Comparison;

use Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\SearchBundle\Query\SearchQueryInterface;
use Oro\Bundle\SearchBundle\Query\Criteria\ExpressionBuilder;
use Oro\Bundle\SearchBundle\Query\Query;

class SearchFilterDatasourceAdapter implements FilterDatasourceAdapterInterface
{
    /**
     * @var SearchQueryInterface
     */
    private $query;

    /**
     * @param SearchQueryInterface $query
     */
    public function __construct(SearchQueryInterface $query)
    {
        $this->query = $query;
    }

    /**
     * @throws \RuntimeException
     */
    public function getDatabasePlatform()
    {
        throw new \RuntimeException(self::class.' has no implementation of `getDatabasePlatform`.');
    }

    /**
     * Adds a new WHERE restriction
     *
     * @param mixed  $restriction The restriction to add.
     * @param string $condition   The condition.
     *                            Can be FilterUtility::CONDITION_OR or FilterUtility::CONDITION_AND.
     * @param bool   $isComputed
     */
    public function addRestriction($restriction, $condition, $isComputed = false)
    {
        if ($restriction instanceof Comparison) {
            $this->query->getQuery()->where(
                $condition == FilterUtility::CONDITION_OR ? Query::KEYWORD_OR : Query::KEYWORD_AND,
                $restriction->getField(),
                $restriction->getOperator(),
                $restriction->getValue()->getValue()
            );

            return;
        }

        throw new \BadMethodCallException('Restriction not supported.');
    }

    /**
     * @param mixed $_
     * @return null
     */
    public function groupBy($_)
    {
        throw new \BadMethodCallException('Method currently not supported.');
    }

    /**
     * @param mixed $_
     * @return null
     */
    public function addGroupBy($_)
    {
        throw new \BadMethodCallException('Method currently not supported.');
    }

    /**
     * @return ExpressionBuilder
     * @deprecated Use Criteria::expr() instead.
     */
    public function expr()
    {
        throw new \BadMethodCallException('Use Criteria::expr() instead.');
    }

    /**
     * @param int|string $key
     * @param mixed $value
     * @param null $type
     * @return null
     */
    public function setParameter($key, $value, $type = null)
    {
        throw new \BadMethodCallException('Method currently not supported.');
    }

    /**
     * @param string $filterName
     * @return string
     */
    public function generateParameterName($filterName)
    {
        return $filterName;
    }

    /**
     * @param string $fieldName
     * @return null|string
     */
    public function getFieldByAlias($fieldName)
    {
        throw new \BadMethodCallException('Method currently not supported.');
    }

    /**
     * @return SearchQueryInterface
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * @return Query
     */
    public function getWrappedSearchQuery()
    {
        if (!$this->query || !$this->query->getQuery()) {
            throw new \RuntimeException('Query not initialized properly');
        }

        return $this->query->getQuery();
    }
}
