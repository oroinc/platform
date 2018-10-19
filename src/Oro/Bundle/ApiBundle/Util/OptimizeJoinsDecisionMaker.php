<?php

namespace Oro\Bundle\ApiBundle\Util;

use Doctrine\Common\Collections\Expr\Comparison;

/**
 * Default implementation of the decision maker to decide whether a specific comparison expression
 * from WHERE clause of a query allows or disallows conversion of a related LEFT join to INNER join.
 */
class OptimizeJoinsDecisionMaker implements OptimizeJoinsDecisionMakerInterface
{
    /**
     * {@inheritdoc}
     */
    public function isOptimizationSupported(Comparison $comparison, $resolvedValue): bool
    {
        switch ($comparison->getOperator()) {
            case 'EXISTS':
                return (bool)$resolvedValue;
            case 'NEQ_OR_NULL':
            case 'NEQ_OR_EMPTY':
            case 'ALL_MEMBER_OF':
                return false;
            case 'EMPTY':
                return !(bool)$resolvedValue;
        }

        return true;
    }
}
