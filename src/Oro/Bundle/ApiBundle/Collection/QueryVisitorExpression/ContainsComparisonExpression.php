<?php

namespace Oro\Bundle\ApiBundle\Collection\QueryVisitorExpression;

use Oro\Bundle\ApiBundle\Collection\QueryExpressionVisitor;

/**
 * Represents LIKE '%value%' comparison expression.
 */
class ContainsComparisonExpression implements ComparisonExpressionInterface
{
    #[\Override]
    public function walkComparisonExpression(
        QueryExpressionVisitor $visitor,
        string $field,
        string $expression,
        string $parameterName,
        mixed $value
    ): mixed {
        $visitor->addParameter(
            $parameterName,
            '%' . ($value instanceof ExpressionValue ? $value->getValue() : $value) . '%'
        );

        return $visitor->getExpressionBuilder()
            ->like($expression, $visitor->buildParameterExpression($parameterName, $value));
    }
}
