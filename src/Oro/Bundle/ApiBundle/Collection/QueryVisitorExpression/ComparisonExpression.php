<?php

namespace Oro\Bundle\ApiBundle\Collection\QueryVisitorExpression;

use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\ORM\Query\Expr;
use Oro\Bundle\ApiBundle\Collection\QueryExpressionVisitor;

/**
 * Represents a comparison expression.
 */
class ComparisonExpression implements ComparisonExpressionInterface
{
    /**
     * {@inheritdoc}
     */
    public function walkComparisonExpression(
        QueryExpressionVisitor $visitor,
        Comparison $comparison,
        $fieldName,
        $parameterName
    ) {
        // set parameter
        $visitor->addParameter($parameterName, $visitor->walkValue($comparison->getValue()));

        // generate expression
        return new Expr\Comparison(
            $fieldName,
            $comparison->getOperator(),
            $visitor->buildPlaceholder($parameterName)
        );
    }
}
