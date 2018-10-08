<?php

namespace Oro\Bundle\ApiBundle\Collection\QueryVisitorExpression;

use Doctrine\ORM\Query\QueryException;
use Oro\Bundle\ApiBundle\Collection\QueryExpressionVisitor;
use Oro\Bundle\ApiBundle\Model\Range;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;

/**
 * Represents ALL MEMBER OF comparison expression that checks
 * whether to-many association contains all of specific values.
 * This expression supports a scalar value and an array of scalar values.
 */
class AllMemberOfComparisonExpression implements ComparisonExpressionInterface
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
            throw new QueryException(\sprintf('The value for "%s" must not be a range.', $field));
        }

        $expectedNumberOfRecordsParameterName = $parameterName . '_expected';
        $expectedNumberOfRecords = \is_array($value)
            ? \count($value)
            : 1;

        $visitor->addParameter($parameterName, $value);
        $visitor->addParameter($expectedNumberOfRecordsParameterName, $expectedNumberOfRecords);

        $subquery = $visitor->createSubquery($field, true);
        $subqueryRootAlias = QueryBuilderUtil::getSingleRootAlias($subquery);
        $subquery->select($subquery->expr()->count($subqueryRootAlias));
        $subquery->andWhere(
            $subquery->expr()->in(
                $subqueryRootAlias,
                $visitor->buildPlaceholder($parameterName)
            )
        );

        return $visitor->getExpressionBuilder()->eq(
            $visitor->buildPlaceholder($expectedNumberOfRecordsParameterName),
            \sprintf('(%s)', $subquery->getDQL())
        );
    }
}
