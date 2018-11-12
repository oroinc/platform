<?php

namespace Oro\Bundle\ApiBundle\Collection\QueryVisitorExpression;

use Oro\Bundle\ApiBundle\Collection\QueryExpressionVisitor;

/**
 * Represents NOT LIKE '%value' comparison expression.
 */
class NotEndsWithComparisonExpression implements ComparisonExpressionInterface
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
        $visitor->addParameter($parameterName, '%' . $value);

        return $visitor->getExpressionBuilder()
            ->notLike($expression, $visitor->buildPlaceholder($parameterName));
    }
}
