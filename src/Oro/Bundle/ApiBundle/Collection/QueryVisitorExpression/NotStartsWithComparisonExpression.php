<?php

namespace Oro\Bundle\ApiBundle\Collection\QueryVisitorExpression;

use Oro\Bundle\ApiBundle\Collection\QueryExpressionVisitor;

/**
 * Represents NOT LIKE 'value%' comparison expression.
 */
class NotStartsWithComparisonExpression implements ComparisonExpressionInterface
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
            ($value instanceof ExpressionValue ? $value->getValue() : $value) . '%'
        );

        return $visitor->getExpressionBuilder()
            ->notLike($expression, $visitor->buildParameterExpression($parameterName, $value));
    }
}
