<?php

namespace Oro\Bundle\ApiBundle\Collection\QueryVisitorExpression;

use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\Query\QueryException;
use Oro\Bundle\ApiBundle\Collection\QueryExpressionVisitor;
use Oro\Bundle\ApiBundle\Model\Range;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;

/**
 * Represents NOT EQUAL TO OR IS NULL comparison expression.
 * This expression supports a scalar value, an array of scalar values and a range value.
 */
class NeqOrNullComparisonExpression implements ComparisonExpressionInterface
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
            // the filter like IS NOT NULL OR IS NULL does not have a sense
            throw new QueryException(sprintf('The value for "%s" must not be NULL.', $field));
        }

        $builder = $visitor->getExpressionBuilder();

        if ($value instanceof Range) {
            $mainExpr = $this->walkRangeExpression($visitor, $field, $parameterName, $value);
        } else {
            $visitor->addParameter($parameterName, $value);
            $mainExpr = $builder->notIn($expression, $visitor->buildPlaceholder($parameterName));
        }

        return $builder->orX($mainExpr, $builder->isNull($expression));
    }

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
                QueryBuilderUtil::getSelectExpr($subquery),
                $visitor->buildPlaceholder($fromParameterName),
                $visitor->buildPlaceholder($toParameterName)
            )
        );
        $subquery->select(QueryBuilderUtil::getSingleRootAlias($subquery));

        $builder = $visitor->getExpressionBuilder();

        return $builder->not($builder->exists($subquery->getDQL()));
    }
}
