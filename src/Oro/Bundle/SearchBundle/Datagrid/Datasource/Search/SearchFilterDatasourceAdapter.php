<?php

namespace Oro\Bundle\SearchBundle\Datagrid\Datasource\Search;

use Doctrine\Common\Collections\Expr\Comparison;
use Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\SearchBundle\Query\Criteria\ExpressionBuilder;
use Oro\Bundle\SearchBundle\Query\Query;

class SearchFilterDatasourceAdapter implements FilterDatasourceAdapterInterface
{
    /**
     * @var Query
     */
    private $query;

    /**
     * @var ExpressionBuilder
     */
    private $expressionBuilder;

    public function __construct(Query $query)
    {
        $this->query = $query;
    }

    public function getDatabasePlatform()
    {
        return null;
    }

    /**
     * Adds a new WHERE or HAVING restriction depends on the given parameters.
     *
     * @param mixed  $restriction The restriction to add.
     * @param string $condition   The condition.
     *                            Can be FilterUtility::CONDITION_OR or FilterUtility::CONDITION_AND.
     * @param bool   $isComputed
     */
    public function addRestriction($restriction, $condition, $isComputed = false)
    {
        if ($restriction instanceof Comparison) {
            $this->query->where(
                $condition == FilterUtility::CONDITION_OR ? Query::KEYWORD_OR : Query::KEYWORD_AND,
                $restriction->getField(),
                $restriction->getOperator(),
                $restriction->getValue()->getValue()
            );

            return;
        }

        throw new \InvalidArgumentException('Restriction not supported.');
    }

    /**
     * @param mixed $_
     * @return null
     */
    public function groupBy($_)
    {
        return null;
    }

    /**
     * @param mixed $_
     * @return null
     */
    public function addGroupBy($_)
    {
        return null;
    }

    public function expr()
    {
        if ($this->expressionBuilder === null) {
            $this->expressionBuilder = new ExpressionBuilder();
        }

        return $this->expressionBuilder;
    }

    /**
     * @param int|string $key
     * @param mixed $value
     * @param null $type
     * @return null
     */
    public function setParameter($key, $value, $type = null)
    {
        return null;
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
        return null;
    }

    /**
     * @return Query
     */
    public function getQuery()
    {
        return $this->query;
    }
}
