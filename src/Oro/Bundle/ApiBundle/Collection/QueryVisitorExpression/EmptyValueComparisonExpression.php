<?php

namespace Oro\Bundle\ApiBundle\Collection\QueryVisitorExpression;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Query\Expr;
use Oro\Bundle\ApiBundle\Collection\QueryExpressionVisitor;

/**
 * Represents the following comparison expressions:
 * * EQUAL TO empty value OR IS NULL
 * * NOT EQUAL TO empty value AND NOT IS NULL
 */
class EmptyValueComparisonExpression implements ComparisonExpressionInterface
{
    /**
     * {@inheritDoc}
     */
    public function walkComparisonExpression(
        QueryExpressionVisitor $visitor,
        string $field,
        string $expression,
        string $parameterName,
        mixed $value
    ): mixed {
        $builder = $visitor->getExpressionBuilder();
        $dataType = $visitor->getFieldDataType();

        if ($value) {
            return $builder->orX(
                $this->buildNullComparisonExpr($visitor, $builder, $dataType, $expression, $parameterName, true),
                $this->buildValueComparisonExpr($visitor, $builder, $dataType, $expression, $parameterName, true)
            );
        }

        return $builder->andX(
            $this->buildNullComparisonExpr($visitor, $builder, $dataType, $expression, $parameterName, false),
            $this->buildValueComparisonExpr($visitor, $builder, $dataType, $expression, $parameterName, false)
        );
    }

    private function buildValueComparisonExpr(
        QueryExpressionVisitor $visitor,
        Expr $builder,
        string $dataType,
        string $expression,
        string $parameterName,
        bool $isEmpty
    ): mixed {
        if (Types::JSON === $dataType || Types::JSON_ARRAY === $dataType) {
            $expression = 'LENGTH(CAST(' . $expression . ' AS text))';
            $visitor->addParameter($parameterName, 2);

            return $isEmpty
                ? $builder->eq($expression, $visitor->buildPlaceholder($parameterName))
                : $builder->gt($expression, $visitor->buildPlaceholder($parameterName));
        }

        if (Types::ARRAY === $dataType) {
            $expression = 'CAST(' . $expression . ' AS text)';
            $visitor->addParameter($parameterName, base64_encode(serialize([])));

            return $isEmpty
                ? $builder->eq($expression, $visitor->buildPlaceholder($parameterName))
                : $builder->neq($expression, $visitor->buildPlaceholder($parameterName));
        }

        $visitor->addParameter($parameterName, '');

        return $isEmpty
            ? $builder->eq($expression, $visitor->buildPlaceholder($parameterName))
            : $builder->neq($expression, $visitor->buildPlaceholder($parameterName));
    }

    private function buildNullComparisonExpr(
        QueryExpressionVisitor $visitor,
        Expr $builder,
        string $dataType,
        string $expression,
        string $parameterName,
        bool $isEmpty
    ): mixed {
        if (Types::ARRAY === $dataType) {
            $parameterName .= '_null';
            $expression = 'CAST(' . $expression . ' AS text)';
            $visitor->addParameter($parameterName, base64_encode(serialize(null)));

            return $isEmpty
                ? $builder->eq($expression, $visitor->buildPlaceholder($parameterName))
                : $builder->neq($expression, $visitor->buildPlaceholder($parameterName));
        }

        return $isEmpty
            ? $builder->isNull($expression)
            : $builder->isNotNull($expression);
    }
}
