<?php

namespace Oro\Bundle\ConfigBundle\Api\Repository;

use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\Common\Collections\Expr\CompositeExpression;
use Doctrine\Common\Collections\Expr\ExpressionVisitor;
use Doctrine\Common\Collections\Expr\Value;

/**
 * Walks an expression graph and collects all mandatory "equals" expressions.
 */
class ConfigurationOptionFilterVisitor extends ExpressionVisitor
{
    private array $filters = [];

    /**
     * @return array [field name => field value, ...]
     */
    public function getFilters(): array
    {
        return $this->filters;
    }

    /**
     * {@inheritDoc}
     */
    public function walkComparison(Comparison $comparison): void
    {
        if (Comparison::EQ !== $comparison->getOperator() && Comparison::IN !== $comparison->getOperator()) {
            throw new \RuntimeException(sprintf(
                'Only "%s" and "%s" operators are supported. Field: %s.',
                Comparison::EQ,
                Comparison::IN,
                $comparison->getField()
            ));
        }
        $this->filters[$comparison->getField()] = $this->walkValue($comparison->getValue());
    }

    /**
     * {@inheritDoc}
     */
    public function walkValue(Value $value): mixed
    {
        return $value->getValue();
    }

    /**
     * {@inheritDoc}
     */
    public function walkCompositeExpression(CompositeExpression $expr): void
    {
        if (CompositeExpression::TYPE_AND !== $expr->getType()) {
            throw new \RuntimeException(sprintf(
                'Only "%s" expression is supported.',
                CompositeExpression::TYPE_AND
            ));
        }
        $expressionList = $expr->getExpressionList();
        foreach ($expressionList as $child) {
            $this->dispatch($child);
        }
    }
}
