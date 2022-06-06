<?php

namespace Oro\Bundle\SearchBundle\Engine\Orm;

use Doctrine\ORM\QueryBuilder;

/**
 * Describes the general mandatory createQueryBuilder operation for search operations.
 * In order to be able to use certain ORM driver for search purposes, it must implement this interface.
 */
interface QueryBuilderCreatorInterface
{
    /**
     * Create a new QueryBuilder instance that is pre-populated for this entity name
     *
     * @param string $alias
     *
     * @return QueryBuilder
     */
    public function createQueryBuilder(string $alias);
}
