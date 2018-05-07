<?php

namespace Oro\Bundle\ApiBundle\Collection\QueryVisitorExpression;

use Oro\Bundle\ApiBundle\Collection\QueryExpressionVisitor;

/**
 * Represents LIKE '%value' comparison expression.
 */
class EndsWithComparisonExpression implements ComparisonExpressionInterface
{
    /**
     * {@inheritdoc}
     */
    public function walkComparisonExpression(
        QueryExpressionVisitor $visitor,
        string $expression,
        string $parameterName,
        $value
    ) {
        $visitor->addParameter($parameterName, '%' . $value);

        return $visitor->getExpressionBuilder()
            ->like($expression, $visitor->buildPlaceholder($parameterName));
    }
}
