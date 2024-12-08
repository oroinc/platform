<?php

namespace Oro\Bundle\ApiBundle\Collection\QueryVisitorExpression;

use Doctrine\ORM\Query\Expr;

/**
 * Represents logical AND expression.
 */
class AndCompositeExpression implements CompositeExpressionInterface
{
    #[\Override]
    public function walkCompositeExpression(array $expressions): mixed
    {
        return new Expr\Andx($expressions);
    }
}
