<?php

namespace Oro\Bundle\ApiBundle\Collection\QueryVisitorExpression;

use Doctrine\ORM\Query\Expr;

/**
 * Represents logical AND expression.
 */
class AndCompositeExpression implements CompositeExpressionInterface
{
    /**
     * {@inheritdoc}
     */
    public function walkCompositeExpression(array $expressions)
    {
        return new Expr\Andx($expressions);
    }
}
