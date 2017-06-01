<?php

namespace Oro\Bundle\UserBundle\Autocomplete\QueryCriteria;

use Doctrine\ORM\QueryBuilder;

/**
 * Class SearchUserCriteria
 * @package Oro\Bundle\UserBundle\Autocomplete
 */
abstract class SearchCriteria
{
    /**
     * Adds a search criteria to the given query builder based on the given query string
     *
     * @param QueryBuilder $queryBuilder The query builder
     * @param string       $search       The search string
     */
    abstract public function addSearchCriteria(QueryBuilder $queryBuilder, $search);
}
