<?php

namespace Oro\Bundle\SearchBundle\Query\Modifier;

use Oro\Bundle\SearchBundle\Query\Query;

/**
 * Defines the contract for modifying search queries.
 *
 * This interface specifies the method for applying modifications to search Query
 * instances, enabling customization of search queries before execution. Implementations
 * can add filters, sorting, pagination, or other query parameters.
 */
interface QueryModifierInterface
{
    public function modify(Query $query);
}
