<?php

namespace Oro\Bundle\ApiBundle\Collection\QueryVisitorExpression;

use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\Common\Collections\Expr\CompositeExpression;
use Doctrine\Common\Collections\Expr\ExpressionVisitor;
use Doctrine\Common\Collections\Expr\Value;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\Query\Parameter;

/**
 * Prepares an expression to be used in the subquery that {@see EntityComparisonExpression} builds.
 * This visitor walks an expression graph and does the following:
 * * converts the expression to ORM expression
 * * sets the subquery alias for field name expressions that do not have any alias
 * * collects all values and replace them with parameter placeholders
 */
class EntityComparisonExpressionVisitor extends ExpressionVisitor
{
    private array $parameters = [];
    private ?Expr $expr = null;

    public function __construct(
        private readonly string $subqueryAlias,
        private readonly string $parameterNamePrefix
    ) {
    }

    /**
     * @return Parameter[]
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    #[\Override]
    public function walkComparison(Comparison $comparison)
    {
        $field = $this->normalizeField($comparison->getField());
        $operator = $comparison->getOperator();
        $value = $this->walkValue($comparison->getValue());

        $parameterName = $this->parameterNamePrefix . '__' . (\count($this->parameters) + 1);
        $placeholder = ':' . $parameterName;

        switch ($operator) {
            case Comparison::EQ:
                if (null === $value) {
                    return $this->expr()->isNull($field);
                }

                $this->parameters[] = new Parameter($parameterName, $value);

                return $this->expr()->eq($field, $placeholder);
            case Comparison::NEQ:
                if (null === $value) {
                    return $this->expr()->isNotNull($field);
                }

                $this->parameters[] = new Parameter($parameterName, $value);

                return $this->expr()->neq($field, $placeholder);
            case Comparison::IN:
                $this->parameters[] = new Parameter($parameterName, $value);

                return $this->expr()->in($field, $placeholder);
            case Comparison::NIN:
                $this->parameters[] = new Parameter($parameterName, $value);

                return $this->expr()->notIn($field, $placeholder);
            case Comparison::CONTAINS:
                $this->parameters[] = new Parameter($parameterName, '%' . $value . '%');

                return $this->expr()->like($field, $placeholder);
            case Comparison::STARTS_WITH:
                $this->parameters[] = new Parameter($parameterName, $value . '%');

                return $this->expr()->like($field, $placeholder);
            case Comparison::ENDS_WITH:
                $this->parameters[] = new Parameter($parameterName, '%' . $value);

                return $this->expr()->like($field, $placeholder);
            case Comparison::GT:
            case Comparison::GTE:
            case Comparison::LT:
            case Comparison::LTE:
                $this->parameters[] = new Parameter($parameterName, $value);

                return new Expr\Comparison($field, $operator, $placeholder);
        }

        throw new \RuntimeException(\sprintf('Unknown comparison operator: %s.', $operator));
    }

    #[\Override]
    public function walkValue(Value $value)
    {
        return $value->getValue();
    }

    #[\Override]
    public function walkCompositeExpression(CompositeExpression $expr)
    {
        $expressionList = [];
        foreach ($expr->getExpressionList() as $child) {
            $expressionList[] = $this->dispatch($child);
        }

        switch ($expr->getType()) {
            case CompositeExpression::TYPE_AND:
                return new Expr\Andx($expressionList);
            case CompositeExpression::TYPE_OR:
                return new Expr\Orx($expressionList);
        }

        throw new \RuntimeException(\sprintf('Unknown composite %s.', $expr->getType()));
    }

    private function normalizeField(string $field): string
    {
        return !str_contains($field, '.')
            ? $this->subqueryAlias . '.' . $field
            : $field;
    }

    private function expr(): Expr
    {
        if (null === $this->expr) {
            $this->expr = new Expr();
        }

        return $this->expr;
    }
}
