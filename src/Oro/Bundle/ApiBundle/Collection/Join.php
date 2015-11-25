<?php

namespace Oro\Bundle\ApiBundle\Collection;

use Doctrine\ORM\Query\Expr\Join as BaseJoin;

class Join extends BaseJoin
{
    /**
     * @param string $joinType
     */
    public function setJoinType($joinType)
    {
        $this->joinType = $joinType;
    }

    /**
     * @param string $alias
     */
    public function setAlias($alias)
    {
        $this->alias = $alias;
    }
}
