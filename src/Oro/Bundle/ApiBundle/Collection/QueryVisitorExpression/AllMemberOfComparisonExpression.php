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

        $visitor->addParameter($parameterName, $value);
        $visitor->addParameter($expectedNumberOfRecordsParameterName, $this->getExpectedNumberOfRecords($value));

        $subquery = $visitor->createSubquery($field, true);
        $subquery->andWhere(
            $subquery->expr()->in(
                QueryBuilderUtil::getSelectExpr($subquery),
                $visitor->buildPlaceholder($parameterName)
            )
        );
        $subquery->select($subquery->expr()->count(QueryBuilderUtil::getSingleRootAlias($subquery)));

        return $visitor->getExpressionBuilder()->eq(
            $visitor->buildPlaceholder($expectedNumberOfRecordsParameterName),
            \sprintf('(%s)', $subquery->getDQL())
        );
    }

    /**
     * @param mixed $value
     *
     * @return int
     */
    protected function getExpectedNumberOfRecords($value): int
    {
        return \is_array($value)
            ? \count($value)
            : 1;
    }
}
