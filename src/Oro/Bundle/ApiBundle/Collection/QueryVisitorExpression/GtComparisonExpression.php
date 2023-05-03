<?php

namespace Oro\Bundle\ApiBundle\Collection\QueryVisitorExpression;

use Oro\Bundle\ApiBundle\Collection\QueryExpressionVisitor;

/**
 * Represents GREATER THAN comparison expression.
 */
class GtComparisonExpression implements ComparisonExpressionInterface
{
    /**
     * {@inheritdoc}
     */
    public function walkComparisonExpression(
        QueryExpressionVisitor $visitor,
        string $field,
        string $expression,
        string $parameterName,
        mixed $value
    ): mixed {
        $visitor->addParameter($parameterName, $value);

        return $visitor->getExpressionBuilder()
            ->gt($expression, $visitor->buildPlaceholder($parameterName));
    }
}
