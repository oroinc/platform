<?php

namespace Oro\Bundle\ApiBundle\Collection\QueryVisitorExpression;

use Doctrine\ORM\Query\QueryException;
use Oro\Bundle\ApiBundle\Collection\QueryExpressionVisitor;
use Oro\Bundle\ApiBundle\Model\Range;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;

/**
 * Represents NOT EQUAL TO OR EMPTY (to-many association does not contain any of specific values
 * or does not contain any records) comparison expression.
 * This expression supports a scalar value, an array of scalar values and a range value.
 */
class NeqOrEmptyComparisonExpression implements ComparisonExpressionInterface
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
        if (null === $value) {
            // the filter like IS NOT NULL OR EMPTY does not have a sense
            throw new QueryException(\sprintf('The value for "%s" must not be NULL.', $field));
        }

        $builder = $visitor->getExpressionBuilder();
        $subquery = $visitor->createSubquery($field);

        if ($value instanceof Range) {
            $fromParameterName = $parameterName . '_from';
            $toParameterName = $parameterName . '_to';
            $visitor->addParameter($fromParameterName, $value->getFromValue());
            $visitor->addParameter($toParameterName, $value->getToValue());

            $subqueryWhereExpr = $subquery->expr()->between(
                QueryBuilderUtil::getSingleRootAlias($subquery),
                $visitor->buildPlaceholder($fromParameterName),
                $visitor->buildPlaceholder($toParameterName)
            );
        } else {
            $visitor->addParameter($parameterName, $value);

            $subqueryWhereExpr = $subquery->expr()->in(
                QueryBuilderUtil::getSingleRootAlias($subquery),
                $visitor->buildPlaceholder($parameterName)
            );
        }
        $subquery->andWhere($subqueryWhereExpr);

        return $builder->not($builder->exists($subquery->getDQL()));
    }
}
