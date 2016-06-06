<?php

namespace Oro\Bundle\ApiBundle\Collection;

use Doctrine\ORM\Query\Expr\Join as BaseJoin;

class Join extends BaseJoin
{
    /**
     * @param string      $joinType      The condition type constant. Either Join::INNER_JOIN or Join::LEFT_JOIN.
     * @param string      $join          The relationship to join.
     * @param string|null $conditionType The condition type constant. Either Join::ON or Join::WITH.
     * @param string|null $condition     The condition for the join.
     * @param string|null $indexBy       The index for the join.
     */
    public function __construct($joinType, $join, $conditionType = null, $condition = null, $indexBy = null)
    {
        // normalize parameters
        if (null !== $conditionType && !$conditionType) {
            $conditionType = null;
        }
        if (null !== $condition && !$condition) {
            $condition = null;
        }
        if (null !== $indexBy && !$indexBy) {
            $indexBy = null;
        }
        if (null !== $conditionType && null === $condition) {
            $conditionType = null;
        }

        parent::__construct($joinType, $join, null, $conditionType, $condition, $indexBy);
    }

    /**
     * @param string $join
     */
    public function setJoin($join)
    {
        $this->join = $join;
    }

    /**
     * @param string $joinType
     */
    public function setJoinType($joinType)
    {
        $this->joinType = $joinType;
    }

    /**
     * @param string $condition
     */
    public function setCondition($condition)
    {
        $this->condition = $condition;
    }

    /**
     * @param string $alias
     */
    public function setAlias($alias)
    {
        $this->alias = $alias;
    }

    /**
     * Determines whether the given join object represents the same join statement as the current one.
     * Please note that the join type is not a part of the comparison.
     *
     * @param Join $join
     *
     * @return bool
     */
    public function equals(Join $join)
    {
        return
            $this->getJoin() === $join->getJoin()
            && $this->getConditionType() === $join->getConditionType()
            && $this->getCondition() === $join->getCondition()
            && $this->getIndexBy() === $join->getIndexBy();
    }
}
