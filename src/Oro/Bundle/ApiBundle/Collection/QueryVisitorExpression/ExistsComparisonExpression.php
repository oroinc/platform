<?php

namespace Oro\Bundle\ApiBundle\Collection\QueryVisitorExpression;

use Oro\Bundle\ApiBundle\Collection\QueryExpressionVisitor;

/**
 * Represents EXISTS (IS NOT NULL) and NOT EXISTS (IS NULL) comparison expressions.
 */
class ExistsComparisonExpression implements ComparisonExpressionInterface
{
    /**
     * {@inheritdoc}
     */
    public function walkComparisonExpression(
        QueryExpressionVisitor $visitor,
        string $field,
        string $expression,
        string $parameterName,
        $value
    ) {
        if ($value) {
            return $visitor->getExpressionBuilder()->isNotNull($expression);
        }

        return $visitor->getExpressionBuilder()->isNull($expression);
    }
}
