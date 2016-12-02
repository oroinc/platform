<?php

namespace Oro\Bundle\ApiBundle\Collection\QueryVisitorExpression;

interface CompositeExpressionInterface
{
    /**
     * Get composite expression by expressions into the target query language output.
     *
     * @param array $expressionList
     *
     * @return mixed
     */
    public function walkCompositeExpression(array $expressionList);
}
