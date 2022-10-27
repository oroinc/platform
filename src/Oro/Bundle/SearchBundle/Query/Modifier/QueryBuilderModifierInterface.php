<?php

namespace Oro\Bundle\SearchBundle\Query\Modifier;

use Doctrine\ORM\QueryBuilder;

interface QueryBuilderModifierInterface
{
    public function modify(QueryBuilder $queryBuilder);
}
