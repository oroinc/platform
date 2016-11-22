<?php

namespace Oro\Bundle\SearchBundle\Query\Modifier;

use Oro\Bundle\SearchBundle\Query\Query;

interface QueryModifierInterface
{
    /**
     * @param Query $query
     */
    public function modify(Query $query);
}
