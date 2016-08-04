<?php

namespace Oro\Bundle\SearchBundle\Extension;

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
     * Perform the engine query.
     *
     * @return mixed
     */
    public function query();

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
}
