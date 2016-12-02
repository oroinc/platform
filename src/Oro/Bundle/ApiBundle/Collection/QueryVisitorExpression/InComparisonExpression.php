<?php

namespace Oro\Bundle\ApiBundle\Collection\QueryVisitorExpression;

use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\ORM\Query\Parameter;
use Doctrine\ORM\Query\Expr;

use Oro\Bundle\ApiBundle\Collection\QueryExpressionVisitor;

class InComparisonExpression implements ComparisonExpressionInterface
{
    /**
     * {@inheritdoc}
     */
    public function walkComparisonExpression(
        QueryExpressionVisitor $expressionVisitor,
        Comparison $comparison,
        $parameterName,
        $field
    ) {
        // set parameter
        $parameter = new Parameter($parameterName, $expressionVisitor->walkValue($comparison->getValue()));
        $expressionVisitor->addParameter($parameter);

        // generate expression
        $expr = new Expr();
        return $expr->in($field, $expressionVisitor->buildPlaceholder($parameterName));
    }
}
