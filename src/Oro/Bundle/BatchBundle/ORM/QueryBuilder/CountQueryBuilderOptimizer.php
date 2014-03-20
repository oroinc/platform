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
        $parts = $this->originalQb->getDQLParts();

        $qb = clone $this->originalQb;
        $qb->setFirstResult(null)
            ->setMaxResults(null)
            ->resetDQLPart('orderBy')
            ->resetDQLPart('select')
            ->resetDQLPart('join')
            ->resetDQLPart('where')
            ->resetDQLPart('having');

        $this->prepareFieldAliases($parts['select']);
        $fieldsToSelect = array($this->getFieldFQN($this->idFieldName));
        if ($parts['groupBy']) {
            $usedAliases = $this->getUsedAliases((array) $parts['groupBy']);
            foreach ($usedAliases as $alias) {
                // Add group by fields to select fields.
                $fieldsToSelect[] = $this->fieldAliases[$alias] . ' as ' . $alias;
                // Remove this alias from aliases list to prevent their normalization in future.
                unset($this->fieldAliases[$alias]);
            }
        }

        $hasJoins = false;
        if ($parts['join']) {
            $hasJoins = $this->addJoins($qb, $parts);
        }
        if ($parts['where']) {
            $qb->where($this->getStringWithReplacedAliases($parts['where']));
        }
        if ($parts['having']) {
            $qb->having($this->getStringWithReplacedAliases($parts['having']));
        }

        $qb->select($fieldsToSelect);
        $qb->distinct($hasJoins);
        $this->fixUnusedParameters($qb);

        return $qb;
    }

    /**
     * Add required JOINs to resulting Query Builder.
     *
     * @param QueryBuilder $qb
     * @param array $parts
     * @return bool
     */
    protected function addJoins(QueryBuilder $qb, array $parts)
    {
        // Collect list of tables which should be added to new query
        $requiredToJoin = array();
        $requiredToJoin = array_merge($requiredToJoin, $this->getUsedTableAliases($parts['where']));
        $requiredToJoin = array_merge($requiredToJoin, $this->getUsedTableAliases($parts['groupBy']));
        $requiredToJoin = array_merge($requiredToJoin, $this->getUsedTableAliases($parts['having']));
        $requiredToJoin = array_merge($requiredToJoin, $this->getUsedJoinAliases($parts['join'], $requiredToJoin));
        $requiredToJoin = array_diff(array_unique($requiredToJoin), array($this->rootAlias));

        /** @var Expr\Join $join */
        $hasJoins = false;
        foreach ($parts['join'][$this->rootAlias] as $join) {
            $alias = $join->getAlias();
            // To count results number join all tables with inner join and required to tables
            if ($join->getJoinType() == Expr\Join::INNER_JOIN || in_array($alias, $requiredToJoin)) {
                $hasJoins = true;
                $condition = $this->getStringWithReplacedAliases($join->getCondition());
                if ($join->getJoinType() == Expr\Join::INNER_JOIN) {
                    $qb->innerJoin(
                        $join->getJoin(),
                        $alias,
                        $join->getConditionType(),
                        $condition,
                        $join->getIndexBy()
                    );
                } else {
                    $qb->leftJoin(
                        $join->getJoin(),
                        $alias,
                        $join->getConditionType(),
                        $condition,
                        $join->getIndexBy()
                    );
                }
            }
        }

        return $hasJoins;
    }

    /**
     * Set original query builder.
     *
     * @param QueryBuilder $originalQb
     */
    protected function setOriginalQueryBuilder(QueryBuilder $originalQb)
    {
        $this->rootAlias = null;
        $this->idFieldName = null;
        $this->fieldAliases = array();

        $this->originalQb = $originalQb;

        $this->rootAlias = current($this->originalQb->getRootAliases());
        $this->initIdFieldName();
    }

    /**
     * Get prepared having string with replaced aliases.
     *
     * @param string|object $originalString
     * @return string
     */
    protected function getStringWithReplacedAliases($originalString)
    {
        return $this->replaceAliasesWithFields((string) $originalString);
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
            foreach ($select->getParts() as $part) {
                $part = preg_replace('/ as /i', ' as ', $part);
                if (strpos($part, ' as ') !== false) {
                    list($field, $alias) = explode(' as ', $part);
                    $this->fieldAliases[trim($alias)] = trim($field);
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
        foreach ($joins[$this->rootAlias] as $join) {
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
                $aliases = array_merge($aliases, $this->getUsedTableAliases($joinCondition));
            }
        }
        return $aliases;
    }

    /**
     * Get list of table aliases mentioned in condition.
     *
     * @param string|object|array $where
     * @return array
     */
    protected function getUsedTableAliases($where)
    {
        $aliases = array();

        if (is_array($where)) {
            foreach ($where as $wherePart) {
                $aliases = array_merge($aliases, $this->getUsedTableAliases($wherePart));
            }
        } else {
            $where = (string) $where;

            if ($where) {
                $where = $this->replaceAliasesWithFields($where);
                // Search for fields in where clause
                preg_match_all('/(\w+\.\w+)/', $where, $matches);
                if (count($matches) > 1) {
                    foreach ($matches[1] as $match) {
                        if (strpos($match, '.') !== false) {
                            $data = explode('.', $match);
                            $aliases[] = $data[0];
                        }
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
            $condition = preg_replace($this->getRegExpQueryForAlias($alias), $field . ' ', $condition);
        }

        return trim($condition);
    }

    /**
     * Get list of aliases used in condition.
     *
     * @param string|object|array $condition
     * @return array
     */
    protected function getUsedAliases($condition)
    {
        $aliases = array();
        if (is_array($condition)) {
            foreach ($condition as $conditionPart) {
                $aliases = array_merge($aliases, $this->getUsedAliases($conditionPart));
            }
        } else {
            $condition = (string) $condition;
            foreach (array_keys($this->fieldAliases) as $alias) {
                if (preg_match($this->getRegExpQueryForAlias($alias), $condition)) {
                    $aliases[] = $alias;
                }
            }
        }

        return $aliases;
    }

    /**
     * Get regular expression for alias checking.
     *
     * @param string $alias
     * @return string
     */
    protected function getRegExpQueryForAlias($alias)
    {
        // Do not match string if it is part of another string or parameter (starts with :)
        $searchRegExpParts = array(
            '^(' . $alias .')$',
            '(?<![A-Za-z0-9_:])(' . $alias .')([^A-Za-z0-9_])',
            '(?<![A-Za-z0-9_:])(' . $alias .')$',
            '^(' . $alias .')([^A-Za-z0-9_])'
        );

        return '/' . implode('|', $searchRegExpParts) . '/';
    }

    /**
     * Initialize the column id of the targeted class.
     *
     * @return string
     */
    protected function initIdFieldName()
    {
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

    /**
     * Get fields fully qualified name
     *
     * @param string $fieldName
     * @return string
     */
    protected function getFieldFQN($fieldName)
    {
        if (strpos($fieldName, '.') === false) {
            $fieldName = $this->rootAlias . '.' . $fieldName;
        }

        return $fieldName;
    }
}
