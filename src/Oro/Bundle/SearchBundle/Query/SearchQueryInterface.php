<?php

namespace Oro\Bundle\SearchBundle\Query;

use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\SearchBundle\Query\Result;

interface SearchQueryInterface
{
    /**
     * A bridge to the indexer, using aggregated query.
     * Returning unprocessed result.
     *
     * @return Result
     */
    public function getResult();

    /**
     * Execute the query() and return postprocessed data.
     *
     * @return mixed
     */
    public function execute();

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

    /**
     * Return number of records of search query without limit parameters
     *
     * @return int
     */
    public function getTotalCount();

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
     * Returning the wrapped Query object.
     *
     * @return Query
     */
    public function getQuery();

    /**
     * Adding a field to be selected from the Search Index database
     * system.
     *
     * @param      $fieldName
     * @param null $enforcedFieldType
     * @return mixed
     */
    public function addSelect($fieldName, $enforcedFieldType = null);

    /**
     * Insert entities array to query from
     *
     * @param array|string $entities
     *
     * @return SearchQueryInterface
     */
    public function from($entities);

    /**
     * Same as from(). Added for clarity.
     *
     * @param $entities
     * @return SearchQueryInterface
     */
    public function setFrom($entities);

    /**
     * Adding an expression to WHERE.
     *
     * @param $expression
     * @return SearchQueryInterface
     */
    public function setWhere($expression);

    /**
     * Returning the aliases found in the select expressions.
     * When adding a select field using addSelect(), a special SQL
     * syntax is supported for renaming fields. This method returns
     * the alias=>original field association array.
     *
     * @return array
     */
    public function getSelectAliases();
}
