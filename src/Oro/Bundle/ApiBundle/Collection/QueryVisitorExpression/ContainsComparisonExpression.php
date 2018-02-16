<?php

namespace Oro\Bundle\ApiBundle\Collection\QueryVisitorExpression;

use Doctrine\Common\Collections\Expr\Comparison;

use Oro\Bundle\ApiBundle\Collection\QueryExpressionVisitor;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;

/**
 * Represents LIKE comparison expression.
 */
class ContainsComparisonExpression implements ComparisonExpressionInterface
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
        QueryBuilderUtil::checkIdentifier($parameterName);
        QueryBuilderUtil::checkField($fieldName);

        // set parameter
        $parameter = $visitor->createParameter($parameterName, $visitor->walkValue($comparison->getValue()));
        $parameter->setValue('%' . $parameter->getValue() . '%', $parameter->getType());
        $visitor->addParameter($parameter);

        // generate expression
        return $visitor->getExpressionBuilder()
            ->like($fieldName, $visitor->buildPlaceholder($parameterName));
    }
}
