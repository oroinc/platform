<?php

namespace Oro\Bundle\ApiBundle\Collection\QueryVisitorExpression;

use Oro\Bundle\ApiBundle\Collection\QueryExpressionVisitor;

/**
 * Represents LESS THAN comparison expression.
 */
class LtComparisonExpression implements ComparisonExpressionInterface
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
            ->lt($expression, $visitor->buildPlaceholder($parameterName));
    }
}
