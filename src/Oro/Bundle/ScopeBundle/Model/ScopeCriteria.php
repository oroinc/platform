<?php

namespace Oro\Bundle\ScopeBundle\Model;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;

class ScopeCriteria implements \IteratorAggregate
{
    const IS_NOT_NULL = 'IS_NOT_NULL';

    /**
     * @var array
     */
    protected $context = [];

    /**
     * @param $context
     */
    public function __construct(array $context)
    {
        $this->context = $context;
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
        foreach ($this->context as $field => $value) {
            if (in_array($field, $ignoreFields)) {
                continue;
            }

            $qb->andWhere($this->resolveBasicCondition($qb, $alias, $field, $value, true));
        }

        return $qb;
    }

    /**
     * @param QueryBuilder $qb
     * @param string $alias
     * @param array $ignoreFields
     * @return QueryBuilder
     */
    public function applyWhere(QueryBuilder $qb, $alias, $ignoreFields = [])
    {
        foreach ($this->context as $field => $value) {
            if (in_array($field, $ignoreFields)) {
                continue;
            }
            $aliasedField = $alias.'.'.$field;
            if ($value === null) {
                $qb->andWhere($qb->expr()->isNull($aliasedField));
            } elseif ($value === self::IS_NOT_NULL) {
                $qb->andWhere($qb->expr()->isNotNull($aliasedField));
            } else {
                $paramName = $alias.'_param_'.$field;
                if (is_array($value)) {
                    $qb->andWhere($qb->expr()->in($aliasedField, ':'.$paramName));
                } else {
                    $qb->andWhere($qb->expr()->eq($aliasedField, ':'.$paramName));
                }
                $qb->setParameter($paramName, $value);
            }
        }

        return $qb;
    }

    /**
     * @param QueryBuilder $qb
     * @param string $alias
     * @param array $ignoreFields
     */
    public function applyToJoin(QueryBuilder $qb, $alias, $ignoreFields = [])
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
    public function applyToJoinWithPriority(QueryBuilder $qb, $alias, $ignoreFields = [])
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
        foreach ($joins as $join) {
            if (is_array($join)) {
                $this->reapplyJoins($qb, $join, $alias, $ignoreFields, $withPriority);
                continue;
            }

            $condition = $join->getCondition();
            $usedFields = $this->getUsedFields($condition, $alias);
            $parts = [$condition];

            if ($join->getAlias() === $alias) {
                foreach ($this->context as $field => $value) {
                    if (in_array($field, $ignoreFields) || in_array($field, $usedFields)) {
                        continue;
                    }
                    $parts[] = $this->resolveBasicCondition($qb, $alias, $field, $value, $withPriority);
                }
            }

            $parts = array_filter($parts);
            $condition = $this->getConditionFromParts($parts, $withPriority);
            $this->applyJoinWithModifiedCondition($qb, $condition, $join);
        }
    }

    /**
     * @param QueryBuilder $qb
     * @param $alias
     * @param $field
     * @param $value
     * @param $withPriority
     * @return array
     */
    protected function resolveBasicCondition(QueryBuilder $qb, $alias, $field, $value, $withPriority)
    {
        $aliasedField = $alias . '.' . $field;
        if ($value === null) {
            $part = $qb->expr()->isNull($aliasedField);
        } elseif ($value === self::IS_NOT_NULL) {
            $part = $qb->expr()->isNotNull($aliasedField);
        } else {
            $paramName = $alias . '_param_' . $field;
            if ($withPriority) {
                $part = $qb->expr()->orX(
                    $qb->expr()->eq($aliasedField, ':'.$paramName),
                    $qb->expr()->isNull($aliasedField)
                );
            } else {
                $part = $qb->expr()->eq($aliasedField, ':'.$paramName);
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

        return implode(" AND ", $parts);
    }

    /**
     * @param QueryBuilder $qb
     * @param string $condition
     * @param Join $join
     */
    protected function applyJoinWithModifiedCondition(QueryBuilder $qb, $condition, Join $join)
    {
        if (Join::INNER_JOIN == $join->getJoinType()) {
            $qb->innerJoin(
                $join->getJoin(),
                $join->getAlias(),
                $join->getConditionType(),
                $condition,
                $join->getIndexBy()
            );
        }
        if (Join::LEFT_JOIN == $join->getJoinType()) {
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
        $parts = explode('AND', $condition);
        foreach ($parts as $part) {
            $matches = [];
            preg_match(sprintf('/%s\.\w+/', $alias), $part, $matches);
            foreach ($matches as $match) {
                $fields[] = explode('.', $match)[1];
            }
        }

        return $fields;
    }
}
