<?php

namespace Oro\Bundle\SearchBundle\Query;

use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\Common\Collections\Expr\CompositeExpression;
use Doctrine\Common\Collections\Expr\ExpressionVisitor;
use Doctrine\Common\Collections\Expr\Value;

use Oro\Bundle\SearchBundle\Query\Criteria\Criteria;

class QueryStringExpressionVisitor extends ExpressionVisitor
{
    /**
     * {@inheritdoc}
     */
    public function walkComparison(Comparison $comparison)
    {

        list($type, $field) = Criteria::explodeFieldTypeName($comparison->getField());

        $value = $comparison->getValue()->getValue();

        if (is_array($value)) {
            $value = sprintf(
                '(%s)',
                implode(', ', $value)
            );
        };

        if ($type === Query::TYPE_TEXT) {
            $value = sprintf(
                '"%s"',
                $value
            );
        }

        return sprintf(
            '%s %s %s %s',
            $type,
            $field,
            Criteria::getSearchOperatorByComparisonOperator($comparison->getOperator()),
            $value
        );
    }

    /**
     * {@inheritdoc}
     */
    public function walkValue(Value $value)
    {
        return $value->getValue();
    }

    /**
     * {@inheritdoc}
     */
    public function walkCompositeExpression(CompositeExpression $expr)
    {
        $expressionList = [];

        foreach ($expr->getExpressionList() as $child) {
            $expressionList[] = $this->dispatch($child);
        }
        return '(' . implode(' ' . strtolower($expr->getType()). ' ', $expressionList) . ')';
    }
}
