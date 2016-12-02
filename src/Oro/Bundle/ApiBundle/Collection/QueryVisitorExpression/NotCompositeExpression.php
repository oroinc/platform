<?php

namespace Oro\Bundle\ApiBundle\Collection\QueryVisitorExpression;

use Doctrine\ORM\Query\Expr;

class NotCompositeExpression implements CompositeExpressionInterface
{
    const TYPE = 'NOT';

    /**
     * {@inheritdoc}
     */
    public function walkCompositeExpression(array $expressionList)
    {
        return new Expr\Func(self::TYPE, $expressionList);
    }
}
