<?php

namespace Oro\Bundle\SearchBundle\Query\Modifier;

use Doctrine\ORM\QueryBuilder;

/**
 * Defines the contract for modifying ORM query builders for search operations.
 *
 * This interface specifies the method for applying modifications to Doctrine ORM
 * QueryBuilder instances used in search operations, enabling customization of
 * database queries that support search functionality.
 */
interface QueryBuilderModifierInterface
{
    public function modify(QueryBuilder $queryBuilder);
}
