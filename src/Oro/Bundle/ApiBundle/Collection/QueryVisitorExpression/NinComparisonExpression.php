<?php

namespace Oro\Bundle\ApiBundle\Collection\QueryVisitorExpression;

use Doctrine\Common\Collections\Expr\Comparison;

use Oro\Bundle\ApiBundle\Collection\QueryExpressionVisitor;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;

/**
 * Represents NOT IN comparison expression.
 */
class NinComparisonExpression implements ComparisonExpressionInterface
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
        $visitor->addParameter($parameterName, $visitor->walkValue($comparison->getValue()));

        // generate expression
        return $visitor->getExpressionBuilder()
            ->notIn($fieldName, $visitor->buildPlaceholder($parameterName));
    }
}
