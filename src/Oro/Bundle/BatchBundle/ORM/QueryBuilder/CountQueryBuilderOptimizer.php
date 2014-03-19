<?php

namespace Oro\Bundle\BatchBundle\ORM\QueryBuilder;

use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;

class CountQueryBuilderOptimizer
{
    /** @var string */
    protected $idFieldName;

    /** @var string */
    protected $rootAlias;

    /** @var QueryBuilder */
    protected $originalQb;

    /** @var array */
    protected $fieldAliases = array();

    /**
     * Get optimized query builder for count calculation.
     *
     * @param QueryBuilder $originalQb
     * @return QueryBuilder
     */
    public function getCountQueryBuilder(QueryBuilder $originalQb)
    {
        $this->setOriginalQueryBuilder($originalQb);

        $qb = clone $this->originalQb;
        // Permanently reset settings not related to count query
        $qb->setFirstResult(null)
            ->setMaxResults(null)
            ->resetDQLPart('orderBy');

        // Reset settings that may be regenerated in count query
        $qb->resetDQLPart('select')
            ->resetDQLPart('join')
            ->resetDQLPart('having');

        $parts = $this->originalQb->getDQLParts();
        $this->prepareFieldAliases($parts['select']);

        // Collect list of tables which should be added to new query
        $ifFieldFQN = $this->getIdFieldFQN();
        $qb->select(array($ifFieldFQN));
        if ($parts['join']) {
            $requiredToJoin = array();
            if ($parts['where']) {
                $requiredToJoin += $this->getUsedTableAliases($parts['where']);
            }
            if ($parts['groupBy']) {
                $requiredToJoin += $this->getUsedTableAliases($parts['groupBy']);
            }
            if ($parts['having']) {
                $requiredToJoin += $this->getUsedTableAliases($parts['having']);
                $qb->having($this->getPreparedHaving($parts['having']));
            }
            $requiredToJoin += $this->getUsedJoinAliases($parts['join'], $requiredToJoin);
            $requiredToJoin = array_unique($requiredToJoin);

            /** @var Expr\Join $join */
            $hasJoins = false;
            foreach ($parts['join'][$this->getRootAlias()] as $join) {
                // To count results number join all tables with inner join and required to tables
                if ($join->getJoinType() == Expr\Join::INNER_JOIN || in_array($join->getAlias(), $requiredToJoin)) {
                    $hasJoins = true;
                    if ($join->getJoinType() == Expr\Join::INNER_JOIN) {
                        $qb->innerJoin(
                            $join->getJoin(),
                            $join->getAlias(),
                            $join->getConditionType(),
                            $join->getCondition(),
                            $join->getIndexBy()
                        );
                    } else {
                        $qb->leftJoin(
                            $join->getJoin(),
                            $join->getAlias(),
                            $join->getConditionType(),
                            $join->getCondition(),
                            $join->getIndexBy()
                        );
                    }
                }
            }
            // In case when count query has joins count each id only once.
            if ($hasJoins) {
                $qb->select(array('DISTINCT ' . $ifFieldFQN));
            }
        }

        $this->fixUnusedParameters($qb);

        return $qb;
    }

    /**
     * @param QueryBuilder $originalQb
     */
    protected function setOriginalQueryBuilder(QueryBuilder $originalQb)
    {
        $this->rootAlias = null;
        $this->idFieldName = null;
        $this->fieldAliases = array();

        $this->originalQb = $originalQb;
    }

    /**
     * Get prepared having string with replaced aliases.
     *
     * @param string|object $originalHaving
     * @return string
     */
    protected function getPreparedHaving($originalHaving)
    {
        if (is_object($originalHaving)) {
            $originalHaving = (string) $originalHaving;
        }

        return $this->replaceAliasesWithFields($originalHaving);
    }

    /**
     * Get mapping of filed aliases to real field expressions.
     *
     * @param array $selects
     * @return array
     */
    protected function prepareFieldAliases($selects)
    {
        /** @var Expr\Select $select */
        foreach ($selects as $select) {
            $parts = $select->getParts();
            if ($parts) {
                foreach ($select->getParts() as $part) {
                    $part = preg_replace('/ as /i', ' as ', $part);
                    if (strpos($part, ' as ') !== false) {
                        list($field, $alias) = explode(' as ', $part);
                        $this->fieldAliases[trim($alias)] = trim($field);
                    }
                }
            }
        }
    }

    /**
     * Removes unused parameters from query builder
     *
     * @param QueryBuilder $qb
     */
    protected function fixUnusedParameters(QueryBuilder $qb)
    {
        $dql = $qb->getDQL();
        $usedParameters = array();
        /** @var $parameter \Doctrine\ORM\Query\Parameter */
        foreach ($qb->getParameters() as $parameter) {
            if ($this->dqlContainsParameter($dql, $parameter->getName())) {
                $usedParameters[$parameter->getName()] = $parameter->getValue();
            }
        }
        $qb->setParameters($usedParameters);
    }

    /**
     * Returns TRUE if $dql contains usage of parameter with $parameterName
     *
     * @param string $dql
     * @param string $parameterName
     * @return bool
     */
    protected function dqlContainsParameter($dql, $parameterName)
    {
        if (is_numeric($parameterName)) {
            $pattern = sprintf('/\?%s[^\w]/', preg_quote($parameterName));
        } else {
            $pattern = sprintf('/\:%s[^\w]/', preg_quote($parameterName));
        }
        return (bool)preg_match($pattern, $dql . ' ');
    }

    /**
     * Get list of table aliases required for correct join of tables mentioned in required aliases.
     *
     * @param array $joins
     * @param array $aliases
     * @return array
     */
    protected function getUsedJoinAliases($joins, $aliases)
    {
        /** @var Expr\Join $join */
        foreach ($joins[$this->getRootAlias()] as $join) {
            if ($join->getJoinType() == Expr\Join::LEFT_JOIN) {
                $joinTable = $join->getJoin();
                $joinCondition = $join->getCondition();
                $alias = $join->getAlias();
                if (in_array($alias, $aliases)) {
                    if (!empty($joinTable)) {
                        $data = explode('.', $joinTable);
                        if (!in_array($data[0], $aliases)) {
                            $aliases[] = $data[0];
                        }
                    }
                    if (!empty($joinCondition)) {
                        $aliases += $this->getUsedTableAliases($joinCondition);
                    }
                }
            }
        }
        return $aliases;
    }

    /**
     * Get list of table aliases mentioned in condition.
     *
     * @param string|object $where
     * @return array
     */
    protected function getUsedTableAliases($where)
    {
        $aliases = array();

        if (is_array($where)) {
            foreach ($where as $wherePart) {
                $aliases += $this->getUsedTableAliases($wherePart);
            }
        } elseif (is_object($where)) {
            $where = (string) $where;
        }

        if (is_string($where)) {
            $where = $this->replaceAliasesWithFields($where);
            // Search for fields in where clause
            preg_match_all('/(\w+\.\w+)/', $where, $matches);
            if ($matches) {
                foreach ($matches[1] as $match) {
                    if (strpos($match, '.') !== false) {
                        $data = explode('.', $match);
                        $aliases[] = $data[0];
                    }
                }
            }
        }

        return $aliases;
    }

    /**
     * Replace field aliases with real fields.
     *
     * @param string $condition
     * @return string
     */
    protected function replaceAliasesWithFields($condition)
    {
        foreach ($this->fieldAliases as $alias => $field) {
            // Do not replace string if it is part of another string or parameter (starts with :)
            $condition = preg_replace('/(?<![A-Za-z0-9_:])(' . $alias .')([^A-Za-z0-9_])/', $field . ' ', $condition);
        }
        return $condition;
    }

    /**
     * Retrieve the column id of the targeted class
     *
     * @return string
     */
    protected function getIdFieldName()
    {
        if (!$this->idFieldName) {
            /** @var $from \Doctrine\ORM\Query\Expr\From */
            $from = current($this->originalQb->getDQLPart('from'));
            $class = $from->getFrom();

            $idNames = $this->originalQb
                ->getEntityManager()
                ->getMetadataFactory()
                ->getMetadataFor($class)
                ->getIdentifierFieldNames();

            $this->idFieldName = current($idNames);
        }

        return $this->idFieldName;
    }

    /**
     * Get id field fully qualified name
     *
     * @return string
     */
    protected function getIdFieldFQN()
    {
        return $this->getFieldFQN($this->getIdFieldName());
    }

    /**
     * Get fields fully qualified name
     *
     * @param string $fieldName
     * @param string|null $parentAlias
     * @return string
     */
    protected function getFieldFQN($fieldName, $parentAlias = null)
    {
        // add the current alias
        if (strpos($fieldName, '.') === false) {
            $fieldName = ($parentAlias ? : $this->getRootAlias()) . '.' . $fieldName;
        }
        return $fieldName;
    }

    /**
     * Gets the root alias of the query
     *
     * @return string
     */
    protected function getRootAlias()
    {
        if (!$this->rootAlias) {
            $this->rootAlias = current($this->originalQb->getRootAliases());
        }
        return $this->rootAlias;
    }
}
