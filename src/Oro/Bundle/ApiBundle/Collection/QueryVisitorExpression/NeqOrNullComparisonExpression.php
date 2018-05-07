<?php

namespace Oro\Bundle\ApiBundle\Collection\QueryVisitorExpression;

use Doctrine\ORM\Query\QueryException;
use Oro\Bundle\ApiBundle\Collection\QueryExpressionVisitor;

/**
 * Represents NOT EQUAL TO OR IS NULL comparison expression.
 */
class NeqOrNullComparisonExpression implements ComparisonExpressionInterface
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
        if (null === $value) {
            // the filter like IS NOT NULL OR IS NULL does not have a sense
            throw new QueryException(\sprintf('The value for "%s" must not be NULL.', $expression));
        }

        $visitor->addParameter($parameterName, $value);

        $builder = $visitor->getExpressionBuilder();

        return $builder->orX(
            $builder->neq($expression, $visitor->buildPlaceholder($parameterName)),
            $builder->isNull($expression)
        );
    }
}
