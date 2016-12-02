<?php

namespace Oro\Bundle\ApiBundle\Collection\QueryVisitorExpression;

use Doctrine\ORM\Query\Expr;

/**
 * Represents logical NOT expression.
 */
class NotCompositeExpression implements CompositeExpressionInterface
{
    /**
     * {@inheritdoc}
     */
    public function walkCompositeExpression(array $expressions)
    {
        return new Expr\Func('NOT', $expressions);
    }
}
