<?php

namespace Oro\Bundle\ApiBundle\Collection\QueryVisitorExpression;

use Doctrine\ORM\Query\Expr;
use Oro\Bundle\ApiBundle\Collection\QueryExpressionVisitor;
use Oro\Bundle\ApiBundle\Model\Range;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;

/**
 * Represents MEMBER OF comparison expression.
 * This expression supports a scalar value, an array of scalar values and a range value.
 */
class MemberOfComparisonExpression implements ComparisonExpressionInterface
{
    /**
     * {@inheritdoc}
     */
    public function walkComparisonExpression(
        QueryExpressionVisitor $visitor,
        string $field,
        string $expression,
        string $parameterName,
        $value
    ) {
        if ($value instanceof Range) {
            return $this->walkRangeExpression($visitor, $field, $parameterName, $value);
        }

        $visitor->addParameter($parameterName, $value);

        return $visitor->getExpressionBuilder()
            ->isMemberOf($visitor->buildPlaceholder($parameterName), $field);
    }

    /**
     * @param QueryExpressionVisitor $visitor
     * @param string                 $field
     * @param string                 $parameterName
     * @param Range                  $value
     *
     * @return Expr\Func
     */
    private function walkRangeExpression(
        QueryExpressionVisitor $visitor,
        string $field,
        string $parameterName,
        Range $value
    ): Expr\Func {
        $fromParameterName = $parameterName . '_from';
        $toParameterName = $parameterName . '_to';

        $visitor->addParameter($fromParameterName, $value->getFromValue());
        $visitor->addParameter($toParameterName, $value->getToValue());

        $subquery = $visitor->createSubquery($field);
        $subquery->andWhere(
            $subquery->expr()->between(
                QueryBuilderUtil::getSingleRootAlias($subquery),
                $visitor->buildPlaceholder($fromParameterName),
                $visitor->buildPlaceholder($toParameterName)
            )
        );

        return $visitor->getExpressionBuilder()
            ->exists($subquery->getDQL());
    }
}
