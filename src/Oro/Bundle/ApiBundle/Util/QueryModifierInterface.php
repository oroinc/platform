<?php

namespace Oro\Bundle\ApiBundle\Util;

use Doctrine\ORM\QueryBuilder;

/**
 * The interface for classes that need to make a modification of a query builder object
 * in order to protect data that can be retrieved via this query builder.
 */
interface QueryModifierInterface
{
    /**
     * Makes modification of the given query builder.
     *
     * @param QueryBuilder $qb             The query builder to modify
     * @param bool         $skipRootEntity Whether the root entity should be protected or not
     */
    public function modify(QueryBuilder $qb, bool $skipRootEntity): void;
}
