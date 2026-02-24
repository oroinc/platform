<?php

namespace Oro\Bundle\ApiBundle\Util;

use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\Common\Collections\Expr\CompositeExpression;
use Doctrine\Common\Collections\Expr\ExpressionVisitor;
use Doctrine\Common\Collections\Expr\Value;

/**
 * Walks an expression graph and collects comparison expressions are used in all comparison expressions.
 * All comparison expressions should be connected by AND operator, otherwise this visitor throws an exception.
 */
class ComparisonExpressionsVisitor extends ExpressionVisitor
{
    private array $comparisons = [];

    /**
     * Gets all comparison expressions are used in a visited expression graph.
     *
     * @return Comparison[]
     */
    public function getComparisons(): array
    {
        return $this->comparisons;
    }

    #[\Override]
    public function walkComparison(Comparison $comparison): void
    {
        $this->comparisons[] = $comparison;
    }

    #[\Override]
    public function walkValue(Value $value): void
    {
    }

    #[\Override]
    public function walkCompositeExpression(CompositeExpression $expr): void
    {
        if (CompositeExpression::TYPE_AND !== $expr->getType()) {
            throw new \InvalidArgumentException(sprintf(
                'Expected "%s" composite expression, but given "%s".',
                CompositeExpression::TYPE_AND,
                $expr->getType()
            ));
        }

        $expressionList = $expr->getExpressionList();
        foreach ($expressionList as $child) {
            $this->dispatch($child);
        }
    }
}
