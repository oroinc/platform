<?php

namespace Oro\Bundle\ApiBundle\Util;

use Doctrine\Common\Collections\Expr\Comparison;

/**
 * Default implementation of the decision maker to decide whether a specific comparison expression
 * from WHERE clause of a query allows or dissalows convertation of a related LEFT join to INNER join.
 */
class OptimizeJoinsDecisionMaker implements OptimizeJoinsDecisionMakerInterface
{
    /**
     * {@inheritdoc}
     */
    public function isOptimizationSupported(Comparison $comparison, $resolvedValue): bool
    {
        return true;
    }
}
