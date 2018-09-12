<?php

namespace Oro\Bundle\ApiBundle\Collection\QueryVisitorExpression;

use Oro\Bundle\ApiBundle\Collection\QueryExpressionVisitor;

/**
 * Provides an interface for different kind of comparison expressions.
 */
interface ComparisonExpressionInterface
{
    /**
     * Builds a comparison expression for a specific field.
     *
     * @param QueryExpressionVisitor $visitor       The visitor that is used to build a query
     * @param string                 $field         The unique name of a field
     * @param string                 $expression    The DQL expression for a field, e.g. LOWER(field)
     * @param string                 $parameterName The name of parameter unique for each field
     * @param mixed                  $value         The value of a field
     *
     * @return mixed
     */
    public function walkComparisonExpression(
        QueryExpressionVisitor $visitor,
        string $field,
        string $expression,
        string $parameterName,
        $value
    );
}
