<?php

namespace Oro\Bundle\SearchBundle\Engine\Orm;

use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\Common\Collections\Expr\CompositeExpression;
use Doctrine\Common\Collections\Expr\ExpressionVisitor;
use Doctrine\Common\Collections\Expr\Value;
use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\SearchBundle\Query\Criteria\Comparison as SearchComparison;
use Oro\Bundle\SearchBundle\Query\Criteria\Criteria;
use Oro\Bundle\SearchBundle\Query\Query;

class OrmExpressionVisitor extends ExpressionVisitor
{
    /** @var BaseDriver */
    protected $driver;

    /** @var QueryBuilder */
    protected $qb;

    /** @var bool */
    protected $setOrderBy;

    /**
     * @param BaseDriver   $driver
     * @param QueryBuilder $qb
     * @param bool         $setOrderBy
     */
    public function __construct(BaseDriver $driver, QueryBuilder $qb, $setOrderBy = false)
    {
        $this->driver     = $driver;
        $this->qb         = $qb;
        $this->setOrderBy = $setOrderBy;
    }

    /**
     * {@inheritdoc}
     */
    public function walkComparison(Comparison $comparison)
    {
        $value = $comparison->getValue()->getValue();
        list($type, $field) = Criteria::explodeFieldTypeName($comparison->getField());

        $index = uniqid();

        $joinField = sprintf('search.%sFields', $type);
        $joinAlias = $this->driver->getJoinAlias($type, $index);
        $this->qb->leftJoin($joinField, $joinAlias);

        $searchCondition = [
            'fieldName'  => $field,
            'condition'  => Criteria::getSearchOperatorByComparisonOperator($comparison->getOperator()),
            'fieldValue' => $value,
            'fieldType'  => $type
        ];

        if ($type === Query::TYPE_TEXT) {
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
     * {@inheritdoc}
     */
    public function walkValue(Value $value)
    {
        $value->getValue();
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

        switch ($expr->getType()) {
            case CompositeExpression::TYPE_AND:
                return '(' . implode(' AND ', $expressionList) . ')';

            case CompositeExpression::TYPE_OR:
                return '(' . implode(' OR ', $expressionList) . ')';

            default:
                throw new \RuntimeException('Unknown composite ' . $expr->getType());
        }
    }
}
