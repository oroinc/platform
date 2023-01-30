<?php

namespace Oro\Bundle\ApiBundle\Collection\QueryVisitorExpression;

/**
 * Provides an interface for different kind of composite expressions.
 */
interface CompositeExpressionInterface
{
    /**
     * Builds a composite expression.
     */
    public function walkCompositeExpression(array $expressions): mixed;
}
