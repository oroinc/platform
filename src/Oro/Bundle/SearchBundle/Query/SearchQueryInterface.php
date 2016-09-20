<?php

namespace Oro\Bundle\SearchBundle\Query;

use Doctrine\Common\Collections\Expr\Expression;

use Oro\Bundle\SearchBundle\Query\Result\Item;

interface SearchQueryInterface
{
    /**
     * Returning the wrapped Query object.
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
     * @param      $fieldName
     * @param null $enforcedFieldType
     * @return mixed
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
     * @return Query
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
     * @return Query
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
     * @return Query
     */
    public function setMaxResults($maxResults);

    /**
     * Get limit parameter
     *
     * @return int
     */
    public function getMaxResults();
}
