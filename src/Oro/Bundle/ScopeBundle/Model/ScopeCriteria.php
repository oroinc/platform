<?php

namespace Oro\Bundle\ScopeBundle\Model;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;

class ScopeCriteria implements \IteratorAggregate
{
    const IS_NOT_NULL = 'IS_NOT_NULL';

    /**
     * @var array
     */
    protected $context = [];

    /**
     * @var array
     */
    protected $fieldsInfo = [];

    /**
     * @param array $context
     * @param array $fieldsInfo
     */
    public function __construct(array $context, array $fieldsInfo)
    {
        $this->context = $context;
        $this->fieldsInfo = $fieldsInfo;
    }

    /**
     * @param QueryBuilder $qb
     * @param string       $alias
     * @param array        $ignoreFields
     *
     * @return QueryBuilder
     */
    public function applyWhereWithPriority(QueryBuilder $qb, $alias, array $ignoreFields = [])
    {
        QueryBuilderUtil::checkIdentifier($alias);
        foreach ($this->context as $field => $value) {
            QueryBuilderUtil::checkIdentifier($field);
            if (in_array($field, $ignoreFields, true)) {
                continue;
            }
            $condition = null;
            if ($this->isAdditionalJoinNeeds($field)) {
                $localAlias = $alias.'_'.$field;
                $condition = $this->resolveBasicCondition($qb, $localAlias, 'id', $value, true);
                $qb->leftJoin($alias.'.'.$field, $alias.'_'.$field, Join::WITH, $condition);
            } else {
                $condition = $this->resolveBasicCondition($qb, $alias, $field, $value, true);
            }
            $qb->andWhere($condition);
        }

        return $qb;
    }

    /**
     * @param QueryBuilder $qb
     * @param string $alias
     * @param array $ignoreFields
     * @return QueryBuilder
     */
    public function applyWhere(QueryBuilder $qb, $alias, array $ignoreFields = [])
    {
        QueryBuilderUtil::checkIdentifier($alias);
        foreach ($this->context as $field => $value) {
            QueryBuilderUtil::checkIdentifier($field);
            if (in_array($field, $ignoreFields, true)) {
                continue;
            }
            $condition = null;
            if ($this->isAdditionalJoinNeeds($field)) {
                $localAlias = $alias.'_'.$field;
                $condition = $this->resolveBasicCondition($qb, $localAlias, 'id', $value, false);
                $qb->leftJoin($alias.'.'.$field, $localAlias, Join::WITH, $condition);
            } else {
                $condition = $this->resolveBasicCondition($qb, $alias, $field, $value, false);
            }
            $qb->andWhere($condition);
        }

        return $qb;
    }

    /**
     * @param QueryBuilder $qb
     * @param string $alias
     * @param array $ignoreFields
     */
    public function applyToJoin(QueryBuilder $qb, $alias, array $ignoreFields = [])
    {
        /** @var Join[] $joins */
        $joins = $qb->getDQLPart('join');
        $qb->resetDQLPart('join');
        $this->reapplyJoins($qb, $joins, $alias, $ignoreFields);
    }

    /**
     * @param QueryBuilder $qb
     * @param string $alias
     * @param array $ignoreFields
     */
    public function applyToJoinWithPriority(QueryBuilder $qb, $alias, array $ignoreFields = [])
    {
        /** @var Join[] $joins */
        $joins = $qb->getDQLPart('join');
        $qb->resetDQLPart('join');
        $this->reapplyJoins($qb, $joins, $alias, $ignoreFields, true);
    }

    /**
     * @param QueryBuilder $qb
     * @param Join[] $joins
     * @param string $alias
     * @param array $ignoreFields
     * @param bool $withPriority
     */
    protected function reapplyJoins(QueryBuilder $qb, array $joins, $alias, array $ignoreFields, $withPriority = false)
    {
        QueryBuilderUtil::checkIdentifier($alias);
        foreach ($joins as $join) {
            if (is_array($join)) {
                $this->reapplyJoins($qb, $join, $alias, $ignoreFields, $withPriority);
                continue;
            }

            $condition = $join->getCondition();
            $usedFields = $this->getUsedFields($condition, $alias);
            $parts = [$condition];
            $additionalJoins = [];
            if ($join->getAlias() === $alias) {
                foreach ($this->context as $field => $value) {
                    if (in_array($field, $ignoreFields, true) || in_array($field, $usedFields, true)) {
                        continue;
                    }
                    if ($this->isAdditionalJoinNeeds($field)) {
                        $localAlias = $alias.'_'.$field;
                        $additionalJoins[$field] = $this->resolveBasicCondition(
                            $qb,
                            $localAlias,
                            'id',
                            $value,
                            $withPriority
                        );
                    } else {
                        $parts[] = $this->resolveBasicCondition($qb, $alias, $field, $value, $withPriority);
                    }
                }
            }

            $parts = array_filter($parts);
            $condition = $this->getConditionFromParts($parts, $withPriority);
            $this->applyJoinWithModifiedCondition($qb, $condition, $join);
            if (!empty($additionalJoins)) {
                $additionalJoins = array_filter($additionalJoins);
                foreach ($additionalJoins as $field => $condition) {
                    QueryBuilderUtil::checkIdentifier($field);
                    $localAlias = $alias.'_'.$field;
                    $qb->leftJoin($alias.'.'.$field, $localAlias, Join::WITH, $condition);
                    if (!$withPriority) {
                        $qb->andWhere($condition);
                    }
                }
            }
        }
    }

    /**
     * @param QueryBuilder $qb
     * @param string $alias
     * @param string $field
     * @param mixed $value
     * @param bool $withPriority
     * @return array
     */
    protected function resolveBasicCondition(QueryBuilder $qb, $alias, $field, $value, $withPriority)
    {
        QueryBuilderUtil::checkIdentifier($alias);
        QueryBuilderUtil::checkIdentifier($field);

        $aliasedField = $alias . '.' . $field;
        if ($value === null) {
            $part = $qb->expr()->isNull($aliasedField);
        } elseif ($value === self::IS_NOT_NULL) {
            $part = $qb->expr()->isNotNull($aliasedField);
        } else {
            $paramName = $alias . '_param_' . $field;
            if (is_array($value)) {
                $comparisonCondition = $qb->expr()->in($aliasedField, ':' . $paramName);
            } else {
                $comparisonCondition = $qb->expr()->eq($aliasedField, ':' . $paramName);
            }
            if ($withPriority) {
                $part = $qb->expr()->orX(
                    $comparisonCondition,
                    $qb->expr()->isNull($aliasedField)
                );
            } else {
                $part = $comparisonCondition;
            }
            $qb->setParameter($paramName, $value);
            if ($withPriority) {
                $qb->addOrderBy($aliasedField, Criteria::DESC);
            }
        }

        return $part;
    }

    /**
     * @param array $parts
     * @param bool $withPriority
     * @return string
     */
    protected function getConditionFromParts(array $parts, $withPriority = false)
    {
        if ($withPriority) {
            $parts = array_map(
                function ($part) {
                    return '(' . $part . ')';
                },
                $parts
            );
        }

        return implode(' AND ', $parts);
    }

    /**
     * @param QueryBuilder $qb
     * @param string $condition
     * @param Join $join
     */
    protected function applyJoinWithModifiedCondition(QueryBuilder $qb, $condition, Join $join)
    {
        if (Join::INNER_JOIN === $join->getJoinType()) {
            $qb->innerJoin(
                $join->getJoin(),
                $join->getAlias(),
                $join->getConditionType(),
                $condition,
                $join->getIndexBy()
            );
        }
        if (Join::LEFT_JOIN === $join->getJoinType()) {
            $qb->leftJoin(
                $join->getJoin(),
                $join->getAlias(),
                $join->getConditionType(),
                $condition,
                $join->getIndexBy()
            );
        }
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return $this->context;
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->context);
    }

    /**
     * @param string $condition
     * @param string $alias
     * @return array
     */
    protected function getUsedFields($condition, $alias)
    {
        $fields = [];
        $parts = explode(' AND ', $condition);
        foreach ($parts as $part) {
            $matches = [];
            preg_match(sprintf('/%s\.\w+/', $alias), $part, $matches);
            foreach ($matches as $match) {
                $fields[] = explode('.', $match)[1];
            }
        }

        return $fields;
    }

    /**
     * @param string $field
     * @return bool
     */
    private function isAdditionalJoinNeeds($field)
    {
        if (isset($this->fieldsInfo[$field])) {
            return in_array($this->fieldsInfo[$field]['relation_type'], ['manyToMany', 'oneToMany']);
        }
        return false;
    }
}
