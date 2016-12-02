<?php

namespace Oro\Bundle\ApiBundle\Collection\QueryVisitorExpression;

use Doctrine\Common\Collections\Expr\Comparison;
use Oro\Bundle\ApiBundle\Collection\QueryExpressionVisitor;

interface ComparisonExpressionInterface
{
    /**
     * Builds a comparison expression.
     *
     * @param QueryExpressionVisitor $visitor
     * @param Comparison             $comparison
     * @param string                 $fieldName
     * @param string                 $parameterName
     *
     * @return mixed
     */
    public function walkComparisonExpression(
        QueryExpressionVisitor $visitor,
        Comparison $comparison,
        $fieldName,
        $parameterName
    );
}
