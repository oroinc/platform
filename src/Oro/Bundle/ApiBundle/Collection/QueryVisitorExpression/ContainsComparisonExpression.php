<?php

namespace Oro\Bundle\ApiBundle\Collection\QueryVisitorExpression;

use Oro\Bundle\ApiBundle\Collection\QueryExpressionVisitor;

/**
 * Represents LIKE '%value%' comparison expression.
 */
class ContainsComparisonExpression implements ComparisonExpressionInterface
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
        $visitor->addParameter($parameterName, '%' . $value . '%');

        return $visitor->getExpressionBuilder()
            ->like($expression, $visitor->buildPlaceholder($parameterName));
    }
}
