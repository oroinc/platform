<?php

namespace Oro\Bundle\ApiBundle\Collection\QueryVisitorExpression;

use Doctrine\ORM\Query\Expr;

/**
 * Represents logical NOT expression.
 */
class NotCompositeExpression implements CompositeExpressionInterface
{
    #[\Override]
    public function walkCompositeExpression(array $expressions): mixed
    {
        return new Expr\Func('NOT', $expressions);
    }
}
