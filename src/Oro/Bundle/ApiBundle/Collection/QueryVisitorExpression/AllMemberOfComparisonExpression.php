<?php

namespace Oro\Bundle\ApiBundle\Collection\QueryVisitorExpression;

use Doctrine\ORM\Query\QueryException;
use Oro\Bundle\ApiBundle\Collection\QueryExpressionVisitor;
use Oro\Bundle\ApiBundle\Model\Range;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;

/**
 * Represents ALL MEMBER OF and ALL NOT MEMBER OF comparison expressions.
 * The ALL MEMBER OF expression checks whether to-many association contains all of specific values.
 * The ALL NOT MEMBER OF expression checks whether to-many association does not contain all of specific values.
 * These expressions support a scalar value and an array of scalar values.
 */
class AllMemberOfComparisonExpression implements ComparisonExpressionInterface
{
    private bool $notExpression;

    public function __construct(bool $notExpression = false)
    {
        $this->notExpression = $notExpression;
    }

    /**
     * {@inheritdoc}
     */
    public function walkComparisonExpression(
        QueryExpressionVisitor $visitor,
        string $field,
        string $expression,
        string $parameterName,
        mixed $value
    ): mixed {
        if ($value instanceof Range) {
            throw new QueryException(sprintf('The value for "%s" must not be a range.', $field));
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
            sprintf('(%s)', $subquery->getDQL())
        );
    }

    private function getExpectedNumberOfRecords(mixed $value): int
    {
        if ($this->notExpression) {
            return 0;
        }

        return \is_array($value)
            ? \count($value)
            : 1;
    }
}
