<?php

namespace Oro\Bundle\SearchBundle\Query\Modifier;

use Oro\Bundle\SearchBundle\Query\Query;

interface QueryModifierInterface
{
    public function modify(Query $query);
}
