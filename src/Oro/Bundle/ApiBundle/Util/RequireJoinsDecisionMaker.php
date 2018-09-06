<?php

namespace Oro\Bundle\ApiBundle\Util;

use Doctrine\Common\Collections\Expr\Comparison;

/**
 * Default implementation of the decision maker to decide whether a specific comparison expression
 * from WHERE clause of a query requires joining of a related entity or not.
 */
class RequireJoinsDecisionMaker implements RequireJoinsDecisionMakerInterface
{
    /**
     * {@inheritdoc}
     */
    public function isJoinRequired(Comparison $comparison, $resolvedValue): bool
    {
        return true;
    }
}
