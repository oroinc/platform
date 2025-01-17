<?php

namespace Oro\Bundle\ApiBundle\Util;

use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\Common\Collections\Expr\CompositeExpression;
use Doctrine\Common\Collections\Expr\ExpressionVisitor;
use Doctrine\Common\Collections\Expr\Value;

/**
 * Walks an expression graph and collects fields are used in all comparison expressions
 * that allow to convert LEFT joins to INNER joins.
 */
class OptimizeJoinsFieldVisitor extends ExpressionVisitor
{
    private OptimizeJoinsDecisionMakerInterface $decisionMaker;
    private array $fields = [];

    public function __construct(OptimizeJoinsDecisionMakerInterface $decisionMaker)
    {
        $this->decisionMaker = $decisionMaker;
    }

    /**
     * Gets all fields are used in a visited expression graph and allow optimization of joins.
     *
     * @return string[]
     */
    public function getFields(): array
    {
        $fields = [];
        foreach ($this->fields as $field => $value) {
            if ($value) {
                $fields[] = $field;
            }
        }

        return $fields;
    }

    #[\Override]
    public function walkComparison(Comparison $comparison)
    {
        $field = $comparison->getField();
        if (!isset($this->fields[$field])) {
            $this->fields[$field] = $this->isOptimizationSupported($comparison);
        } elseif ($this->fields[$field] && !$this->isOptimizationSupported($comparison)) {
            $this->fields[$field] = false;
        }
    }

    #[\Override]
    public function walkValue(Value $value)
    {
        return $value->getValue();
    }

    #[\Override]
    public function walkCompositeExpression(CompositeExpression $expr)
    {
        $expressionList = $expr->getExpressionList();
        foreach ($expressionList as $child) {
            $this->dispatch($child);
        }
    }

    private function isOptimizationSupported(Comparison $comparison): bool
    {
        return $this->decisionMaker->isOptimizationSupported(
            $comparison,
            $this->walkValue($comparison->getValue())
        );
    }
}
