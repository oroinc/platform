<?php

namespace Oro\Bundle\ApiBundle\Util;

use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\Common\Collections\Expr\CompositeExpression;
use Doctrine\Common\Collections\Expr\ExpressionVisitor;
use Doctrine\Common\Collections\Expr\Value;

/**
 * Walks an expression graph and collects fields are used in all comparison expressions
 * that require joining of a related entity.
 */
class RequireJoinsFieldVisitor extends ExpressionVisitor
{
    private RequireJoinsDecisionMakerInterface $decisionMaker;
    private array $fields = [];

    public function __construct(RequireJoinsDecisionMakerInterface $decisionMaker)
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
        if (!$field) {
            return;
        }
        if (!isset($this->fields[$field])) {
            $this->fields[$field] = $this->isJoinRequired($comparison);
        } elseif (!$this->fields[$field] && $this->isJoinRequired($comparison)) {
            $this->fields[$field] = true;
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

    private function isJoinRequired(Comparison $comparison): bool
    {
        return $this->decisionMaker->isJoinRequired(
            $comparison,
            $this->walkValue($comparison->getValue())
        );
    }
}
