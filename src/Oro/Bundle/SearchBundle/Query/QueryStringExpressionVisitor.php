<?php

namespace Oro\Bundle\SearchBundle\Query;

use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\Common\Collections\Expr\CompositeExpression;
use Doctrine\Common\Collections\Expr\ExpressionVisitor;
use Doctrine\Common\Collections\Expr\Value;
use Oro\Bundle\SearchBundle\Query\Criteria\Criteria;

/**
 * Converts search query expressions to query string format.
 *
 * This visitor traverses expression trees and converts them into search query
 * string format, handling comparisons, composite expressions, and values. It
 * properly formats field names, operators, and values according to search query
 * syntax requirements, including proper quoting and array formatting.
 */
class QueryStringExpressionVisitor extends ExpressionVisitor
{
    #[\Override]
    public function walkComparison(Comparison $comparison)
    {
        list($type, $field) = Criteria::explodeFieldTypeName($comparison->getField());

        $value = $comparison->getValue()->getValue();

        if ($type === Query::TYPE_TEXT && is_string($value)) {
            $value = sprintf(
                '"%s"',
                $value
            );
        }

        if (is_array($value)) {
            $value = sprintf(
                '(%s)',
                implode(', ', $value)
            );
        }

        return trim(sprintf(
            '%s %s %s %s',
            $type,
            $field,
            Criteria::getSearchOperatorByComparisonOperator($comparison->getOperator()),
            $value
        ));
    }

    #[\Override]
    public function walkValue(Value $value)
    {
        return $value->getValue();
    }

    #[\Override]
    public function walkCompositeExpression(CompositeExpression $expr)
    {
        $expressionList = [];

        foreach ($expr->getExpressionList() as $child) {
            $expressionList[] = $this->dispatch($child);
        }
        return '(' . implode(' ' . strtolower($expr->getType()) . ' ', $expressionList) . ')';
    }
}
