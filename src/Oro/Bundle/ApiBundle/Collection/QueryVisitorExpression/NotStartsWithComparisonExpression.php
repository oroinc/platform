<?php

namespace Oro\Bundle\ApiBundle\Collection\QueryVisitorExpression;

use Doctrine\Common\Collections\Expr\Comparison;
use Oro\Bundle\ApiBundle\Collection\QueryExpressionVisitor;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;

/**
 * Represents NOT LIKE 'value%' comparison expression.
 */
class NotStartsWithComparisonExpression implements ComparisonExpressionInterface
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
        $parameter = $visitor->createParameter($parameterName, $visitor->walkValue($comparison->getValue()));
        $parameter->setValue($parameter->getValue() . '%', $parameter->getType());
        $visitor->addParameter($parameter);

        // generate expression
        return $visitor->getExpressionBuilder()
            ->notLike($fieldName, $visitor->buildPlaceholder($parameterName));
    }
}
