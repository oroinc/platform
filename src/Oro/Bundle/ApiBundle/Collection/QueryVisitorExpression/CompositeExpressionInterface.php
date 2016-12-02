<?php

namespace Oro\Bundle\ApiBundle\Collection\QueryVisitorExpression;

interface CompositeExpressionInterface
{
    /**
     * Builds a composite expression.
     *
     * @param array $expressions
     *
     * @return mixed
     */
    public function walkCompositeExpression(array $expressions);
}
