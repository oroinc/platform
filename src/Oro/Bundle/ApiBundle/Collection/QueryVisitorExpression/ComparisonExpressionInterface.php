<?php

namespace Oro\Bundle\ApiBundle\Collection\QueryVisitorExpression;

use Doctrine\Common\Collections\Expr\Comparison;
use Oro\Bundle\ApiBundle\Collection\QueryExpressionVisitor;

interface ComparisonExpressionInterface
{
    /**
     * Get expression by comparison expression into the target query language output.
     *
     * @param QueryExpressionVisitor $expressionVisitor
     * @param Comparison             $comparison
     * @param string                 $parameterName
     * @param string                 $field
     *
     * @return mixed
     */
    public function walkComparisonExpression(
        QueryExpressionVisitor $expressionVisitor,
        Comparison $comparison,
        $parameterName,
        $field
    );
}
