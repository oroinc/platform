<?php

namespace Oro\Bundle\ApiBundle\Collection\QueryVisitorExpression;

use Doctrine\ORM\Query\Expr;

/**
 * Represents logical OR expression.
 */
class OrCompositeExpression implements CompositeExpressionInterface
{
    #[\Override]
    public function walkCompositeExpression(array $expressions): mixed
    {
        return new Expr\Orx($expressions);
    }
}
