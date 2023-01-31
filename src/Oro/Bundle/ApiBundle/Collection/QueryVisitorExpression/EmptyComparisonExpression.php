<?php

namespace Oro\Bundle\ApiBundle\Collection\QueryVisitorExpression;

use Oro\Bundle\ApiBundle\Collection\QueryExpressionVisitor;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;

/**
 * Represents EMPTY (to-many association does not contain any records)
 * and NOT EMPTY (to-many association contains at least one record) comparison expressions.
 */
class EmptyComparisonExpression implements ComparisonExpressionInterface
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
        $subquery = $visitor->createSubquery($field);
        $subquery->select(QueryBuilderUtil::getSingleRootAlias($subquery));

        $builder = $visitor->getExpressionBuilder();
        $expr = $builder->exists($subquery->getDQL());
        if ($value) {
            $expr = $builder->not($expr);
        }

        return $expr;
    }
}
