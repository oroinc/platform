<?php

namespace Oro\Bundle\SearchBundle\Engine\Orm;

use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\Common\Collections\Expr\CompositeExpression;
use Doctrine\Common\Collections\Expr\ExpressionVisitor;
use Doctrine\Common\Collections\Expr\Value;
use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\SearchBundle\Query\Query;

class OrmExpressionVisitor extends ExpressionVisitor
{
    /** @var BaseDriver */
    protected $driver;

    /** @var QueryBuilder */
    protected $qb;

    protected $setOrderBy;

    /**
     * @param BaseDriver   $driver
     * @param QueryBuilder $qb
     */
    public function __construct(BaseDriver $driver, QueryBuilder $qb, $setOrderBy = false)
    {
        $this->driver = $driver;
        $this->qb     = $qb;
        $this->setOrderBy;
    }

    /**
     * Converts a comparison expression into the target query language output.
     *
     * @param Comparison $comparison
     *
     * @return mixed
     */
    public function walkComparison(Comparison $comparison)
    {
        $value = $comparison->getValue()->getValue();
        list($type, $field) = $this->getFieldInfo($comparison->getField());

        $index = uniqid();

        $joinField = sprintf('search.%sFields', $type);
        $joinAlias = $this->driver->getJoinAlias($type, $index);
        $this->qb->leftJoin($joinField, $joinAlias);

        $searchCondition = [
            'fieldName'  => $field,
            'condition'  => $this->getSearchOperatorByComparisonOperator($comparison->getOperator()),
            'fieldValue' => $value,
            'fieldType'  => $type
        ];

        if ($type == Query::TYPE_TEXT) {
            if ($searchCondition['fieldValue'] === '') {
                $this->qb->setParameter('field' . $index, $searchCondition['fieldName']);
                return $joinAlias . '.field = :field' . $index;
            } else {
                return $this->driver->addTextField($this->qb, $index, $searchCondition, $this->setOrderBy);
            }
        }

        return $this->driver->addNonTextField($this->qb, $index, $searchCondition);
    }

    /**
     * Converts a value expression into the target query language part.
     *
     * @param Value $value
     *
     * @return mixed
     */
    public function walkValue(Value $value)
    {
        $a = 1;
        // TODO: Implement walkValue() method.
    }

    /**
     * Converts a composite expression into the target query language output.
     *
     * @param CompositeExpression $expr
     *
     * @return mixed
     */
    public function walkCompositeExpression(CompositeExpression $expr)
    {
        $expressionList = [];

        foreach ($expr->getExpressionList() as $child) {
            $expressionList[] = $this->dispatch($child);
        }

        switch ($expr->getType()) {
            case CompositeExpression::TYPE_AND:
                return '(' . implode(' AND ', $expressionList) . ')';

            case CompositeExpression::TYPE_OR:
                return '(' . implode(' OR ', $expressionList) . ')';

            default:
                throw new \RuntimeException("Unknown composite " . $expr->getType());
        }
    }

    protected function getFieldInfo($field)
    {
        $fieldType = Query::TYPE_TEXT;
        if (strpos($field, '.') !== false) {
            list($fieldType, $field) = explode('.', $field);
        }

        return [$fieldType, $field];
    }

    protected function getSearchOperatorByComparisonOperator($operator)
    {
        switch ($operator) {
            case Comparison::CONTAINS:
                return Query::OPERATOR_CONTAINS;
            case Comparison::NEQ:
                return Query::OPERATOR_NOT_EQUALS;
            case Comparison::NIN:
                return Query::OPERATOR_NOT_IN;
        }

        return strtolower($operator);
    }
}
