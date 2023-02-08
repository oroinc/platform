<?php

namespace Oro\Bundle\ApiBundle\Collection\QueryVisitorExpression;

use Doctrine\ORM\Query\QueryException;
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
        mixed $value
    ): mixed {
        if (null === $value) {
            // the filter like NULL MEMBER OF COLLECTION does not have a sense
            throw new QueryException(sprintf('The value for "%s" must not be NULL.', $field));
        }

        $subquery = $visitor->createSubquery($field, true);

        if ($value instanceof Range) {
            $fromParameterName = $parameterName . '_from';
            $toParameterName = $parameterName . '_to';
            $visitor->addParameter($fromParameterName, $value->getFromValue());
            $visitor->addParameter($toParameterName, $value->getToValue());

            $subqueryWhereExpr = $subquery->expr()->between(
                QueryBuilderUtil::getSelectExpr($subquery),
                $visitor->buildPlaceholder($fromParameterName),
                $visitor->buildPlaceholder($toParameterName)
            );
        } else {
            $visitor->addParameter($parameterName, $value);

            $subqueryWhereExpr = $subquery->expr()->in(
                QueryBuilderUtil::getSelectExpr($subquery),
                $visitor->buildPlaceholder($parameterName)
            );
        }

        $subquery->andWhere($subqueryWhereExpr);
        $subquery->select(QueryBuilderUtil::getSingleRootAlias($subquery));

        return $visitor->getExpressionBuilder()->exists($subquery->getDQL());
    }
}
