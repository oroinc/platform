<?php

namespace Oro\Bundle\SearchBundle\Query;

use Doctrine\Common\Collections\Expr\Expression;
use Oro\Bundle\SearchBundle\Query\Criteria\Criteria;
use Oro\Bundle\SearchBundle\Query\Result\Item;

interface SearchQueryInterface
{
    /**
     * Returning the wrapped Query object. Should be used only at storage level.
     *
     * @return Query
     */
    public function getQuery();

    /**
     * Execute the query() and return postprocessed data.
     *
     * @return Item[]
     */
    public function execute();

    /**
     * Returning unprocessed result.
     *
     * @return Result
     */
    public function getResult();

    /**
     * Return number of records of search query without limit parameters
     *
     * @return int
     */
    public function getTotalCount();

    /**
     * Adding a field to be selected from the Search Index database system.
     *
     * @param string|array $fieldName
     * @param null $enforcedFieldType
     * @return SearchQueryInterface
     */
    public function addSelect($fieldName, $enforcedFieldType = null);

    /**
     * Returns the columns that are being selected from the DB.
     * Ignores aliases information.
     *
     * @return array
     */
    public function getSelect();

    /**
     * Returning the aliases found in the select expressions.
     * When adding a select field using addSelect(), a special SQL
     * syntax is supported for renaming fields. This method returns
     * the alias=>original field association array.
     * Note that it won't return fields without aliases set.
     *
     * @return array
     */
    public function getSelectAliases();

    /**
     * Returns the data fields that are returned in the results.
     * Fields can contain type prefixes. Aliases are respected.
     * Result is a combination of getSelect() and getSelectAliases().
     *
     * @return array
     */
    public function getSelectDataFields();

    /**
     * Returning the WHERE clause parts. Should be used only for internal purposes.
     *
     * @return Criteria
     */
    public function getCriteria();

    /**
     * Same as from(). Added for clarity.
     *
     * @param array|string $entities
     * @return SearchQueryInterface
     */
    public function setFrom($entities);

    /**
     * Adding an expression to WHERE.
     *
     * @param Expression  $expression
     * @param null|string $type
     * @return SearchQueryInterface
     */
    public function addWhere(Expression $expression, $type = AbstractSearchQuery::WHERE_AND);

    /**
     * Set order by
     *
     * @param string $fieldName
     * @param string $direction
     * @param string $type
     *
     * @return SearchQueryInterface
     */
    public function setOrderBy($fieldName, $direction = Query::ORDER_ASC, $type = Query::TYPE_TEXT);

    /**
     * Get order by field
     *
     * @return string
     */
    public function getSortBy();

    /**
     * Getting the sort order, i.e. "ASC" etc.
     *
     * @return string
     */
    public function getSortOrder();

    /**
     * Set first result offset
     *
     * @param int $firstResult
     *
     * @return SearchQueryInterface
     */
    public function setFirstResult($firstResult);

    /**
     * Get first result offset
     *
     * @return int
     */
    public function getFirstResult();

    /**
     * Set max results
     *
     * @param int $maxResults
     *
     * @return SearchQueryInterface
     */
    public function setMaxResults($maxResults);

    /**
     * Get limit parameter
     *
     * @return int
     */
    public function getMaxResults();

    /**
     * Add aggregating operation to a search query
     *
     * @param string $name Name of the aggregating
     * @param string $field Fields that should be used to perform aggregating
     * @param string $function Applied aggregating function
     * @return SearchQueryInterface
     */
    public function addAggregate($name, $field, $function);

    /**
     * Return list of all applied aggregating operations
     *
     * @return array ['<name>' => ['field' => <field>, 'function' => '<function>']]
     */
    public function getAggregations();
}
