<?php

namespace Oro\Bundle\ApiBundle\Collection\QueryVisitorExpression;

use Doctrine\Common\Collections\Expr\Comparison;

use Oro\Bundle\ApiBundle\Collection\QueryExpressionVisitor;

/**
 * Represents IN comparison expression.
 */
class InComparisonExpression implements ComparisonExpressionInterface
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
        return $visitor->getExpressionBuilder()
            ->in($fieldName, $visitor->buildPlaceholder($parameterName));
    }
}
