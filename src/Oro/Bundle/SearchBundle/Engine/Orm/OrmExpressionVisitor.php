<?php

namespace Oro\Bundle\SearchBundle\Engine\Orm;

use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\Common\Collections\Expr\CompositeExpression;
use Doctrine\Common\Collections\Expr\ExpressionVisitor;
use Doctrine\Common\Collections\Expr\Value;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\SearchBundle\Query\Criteria\Comparison as SearchComparison;
use Oro\Bundle\SearchBundle\Query\Criteria\Criteria;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;

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
        list($type, $field) = $this->explodeCombinedFieldString($comparison->getField());
        QueryBuilderUtil::checkIdentifier($type);
        $condition = Criteria::getSearchOperatorByComparisonOperator($comparison->getOperator());

        list($joinAlias, $index) = $this->driver->getJoinAttributes($field, $type, $this->qb->getAllAliases());
        QueryBuilderUtil::checkIdentifier($joinAlias);
        QueryBuilderUtil::checkIdentifier($index);
        $joinField = $this->driver->getJoinField($type);

        $searchCondition = [
            'fieldName'  => $field,
            'condition'  => $condition,
            'fieldValue' => $value,
            'fieldType'  => $type
        ];

        $fieldConditionParam = 'field' . $index;

        if (in_array($comparison->getOperator(), SearchComparison::$filteringOperators, true)) {
            $fieldCondition = is_array($searchCondition['fieldName'])
                ? $joinAlias . '.field IN (:field' . $index . ')'
                : $joinAlias . '.field = :field' . $index;

            $this->qb->leftJoin($joinField, $joinAlias, Join::WITH, $fieldCondition);
            $this->qb->setParameter($fieldConditionParam, $searchCondition['fieldName']);

            return $this->driver->addFilteringField($index, $searchCondition);
        }

        if (is_string($searchCondition['fieldName'])) {
            $this->qb->leftJoin($joinField, $joinAlias, Join::WITH, $joinAlias . '.field = :field' . $index);
            $this->qb->setParameter($fieldConditionParam, $searchCondition['fieldName']);
        } else {
            $this->qb->innerJoin($joinField, $joinAlias);
        }

        if ($type === Query::TYPE_TEXT && !in_array($condition, [Query::OPERATOR_IN, Query::OPERATOR_NOT_IN], true)) {
            if ($searchCondition['fieldValue'] === '') {
                $this->qb->setParameter($fieldConditionParam, $searchCondition['fieldName']);

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
        $expressionObjectList = [];

        $expressions    = $expr->getExpressionList();
        $lineExpression = true;
        foreach ($expressions as $child) {
            if ($child instanceof CompositeExpression) {
                $lineExpression       = false;
                $expressionObjectList = $expressions;
                break;
            }
        }

        // optimize search query.
        if ($lineExpression) {
            /** @var Comparison $child */
            foreach ($expressions as $child) {
                $fieldName          = $child->getField();
                $operator           = $child->getOperator();
                $value              = $child->getValue()->getValue();
                $fieldType          = Criteria::explodeFieldTypeName($fieldName)[0];

                if (CompositeExpression::TYPE_AND !== $expr->getType() && $fieldType !== Query::TYPE_TEXT) {
                    $fieldParam = $fieldType;
                } else {
                    $fieldParam = $fieldName;
                }

                $key                = $this->getExpressionKey($fieldParam, $operator, $value);
                $combinedExpression = $child;
                if ($fieldType !== Query::TYPE_TEXT && array_key_exists($key, $expressionObjectList)) {
                    $combinedExpression = $expressionObjectList[$key];

                    $combinedExpression = new Comparison(
                        $this->combineFieldNames($combinedExpression->getField(), $fieldName),
                        $operator,
                        $value
                    );
                }
                $expressionObjectList[$key] = $combinedExpression;
            }
        }

        return $this->walkExpressionObjectList($expr, $expressionObjectList);
    }

    /**
     * @param CompositeExpression $expr
     * @param array $expressionObjectList
     * @return null|string
     * @throws \RuntimeException
     */
    protected function walkExpressionObjectList(CompositeExpression $expr, array $expressionObjectList)
    {
        $expressionList       = [];
        foreach ($expressionObjectList as $child) {
            $childExpr = $this->dispatch($child);
            if ($childExpr) {
                $expressionList[] = $childExpr;
            }
        }

        if (!$expressionList) {
            return null;
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

    /**
     * @param string $fieldParam
     * @param string $operator
     * @param mixed  $value
     *
     * @return string
     */
    protected function getExpressionKey($fieldParam, $operator, $value)
    {
        $value = is_array($value) ? serialize($value) : (string)$value;
        return md5($fieldParam . '|' . $operator . '|' . $value);
    }

    /**
     * @param string $arrayFields
     * @param string $additionalField
     *
     * @return string
     */
    protected function combineFieldNames($arrayFields, $additionalField)
    {
        list($type, $field) = Criteria::explodeFieldTypeName($additionalField);
        $fieldsString = implode(
            '|',
            array_merge(explode('|', Criteria::explodeFieldTypeName($arrayFields)[1]), [$field])
        );

        return Criteria::implodeFieldTypeName($type, $fieldsString);
    }

    /**
     * @param string $fieldString
     *
     * @return array
     */
    protected function explodeCombinedFieldString($fieldString)
    {
        list($type, $field) = Criteria::explodeFieldTypeName($fieldString);
        if (strpos($field, '|') !== false) {
            $field = explode('|', $field);
        }

        return [$type, $field];
    }
}
