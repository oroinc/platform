<?php

namespace Oro\Bundle\ApiBundle\Collection;

use Doctrine\ORM\Query\Expr\Join as BaseJoin;

/**
 * The expression class for DQL join with some additional methods required to build API DQL expressions.
 */
class Join extends BaseJoin
{
    /**
     * @param string      $joinType      The condition type constant. Either Join::INNER_JOIN or Join::LEFT_JOIN.
     * @param string      $join          The relationship to join.
     * @param string|null $conditionType The condition type constant. Either Join::ON or Join::WITH.
     * @param string|null $condition     The condition for the join.
     * @param string|null $indexBy       The index for the join.
     */
    public function __construct(
        string $joinType,
        string $join,
        ?string $conditionType = null,
        ?string $condition = null,
        ?string $indexBy = null
    ) {
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

    public function setJoin(string $join): void
    {
        $this->join = $join;
    }

    public function setJoinType(string $joinType): void
    {
        $this->joinType = $joinType;
    }

    public function setCondition(string $condition): void
    {
        $this->condition = $condition;
    }

    public function setAlias(string $alias): void
    {
        $this->alias = $alias;
    }

    /**
     * Determines whether the given join object represents the same join statement as the current one.
     * Please note that the join type is not a part of the comparison.
     */
    public function equals(Join $join): bool
    {
        return
            $this->getJoin() === $join->getJoin()
            && $this->getConditionType() === $join->getConditionType()
            && $this->getCondition() === $join->getCondition()
            && $this->getIndexBy() === $join->getIndexBy();
    }
}
