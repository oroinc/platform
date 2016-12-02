<?php

namespace Oro\Bundle\ApiBundle\Collection\QueryVisitorExpression;

use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\ORM\Query\Parameter;
use Doctrine\ORM\Query\Expr;

use Oro\Bundle\ApiBundle\Collection\QueryExpressionVisitor;

class ContainsComparisonExpression implements ComparisonExpressionInterface
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
        $parameter->setValue('%' . $parameter->getValue() . '%', $parameter->getType());
        $expressionVisitor->addParameter($parameter);

        // generate expression
        $expr = new Expr();
        return $expr->like($field, $expressionVisitor->buildPlaceholder($parameterName));
    }
}
