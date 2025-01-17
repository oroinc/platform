<?php

namespace Oro\Bundle\ApiBundle\Util;

use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\Common\Collections\Expr\CompositeExpression;
use Doctrine\Common\Collections\Expr\ExpressionVisitor;
use Doctrine\Common\Collections\Expr\Value;

/**
 * Walks an expression graph and replaces a placeholder in a field path with corresponding object names.
 */
class NormalizeExpressionVisitor extends ExpressionVisitor
{
    use NormalizeFieldTrait;

    private array $placeholders;

    public function __construct(array $placeholders)
    {
        $this->placeholders = $placeholders;
    }

    #[\Override]
    public function walkComparison(Comparison $comparison)
    {
        return new Comparison(
            $this->normalizeField($comparison->getField(), $this->placeholders),
            $comparison->getOperator(),
            $this->walkValue($comparison->getValue())
        );
    }

    #[\Override]
    public function walkValue(Value $value)
    {
        return $value;
    }

    #[\Override]
    public function walkCompositeExpression(CompositeExpression $expr)
    {
        return new CompositeExpression(
            $expr->getType(),
            array_map(
                function ($child) {
                    return $this->dispatch($child);
                },
                $expr->getExpressionList()
            )
        );
    }
}
