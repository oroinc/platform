<?php

namespace Oro\Bundle\ApiBundle\Util;

use Doctrine\Common\Collections\Expr\Comparison;

/**
 * An interface for classes that can make a decision whether a specific comparison expression
 * from WHERE clause of a query allows or disallows conversion of a related LEFT join to INNER join.
 */
interface OptimizeJoinsDecisionMakerInterface
{
    /**
     * Determines whether the given comparison expression from WHERE clause of a query
     * allows or disallows conversion of a related LEFT join to INNER join.
     *
     * @param Comparison $comparison    The comparison expression to check
     * @param mixed      $resolvedValue The resolved value of the comparison expression
     *
     * @return bool TRUE if a related LEFT join can be converted to INNER join
     */
    public function isOptimizationSupported(Comparison $comparison, $resolvedValue): bool;
}
