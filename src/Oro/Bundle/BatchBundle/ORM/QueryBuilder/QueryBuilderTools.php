<?php

namespace Oro\Bundle\BatchBundle\ORM\QueryBuilder;

use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;

class QueryBuilderTools extends AbstractQueryBuilderTools
{
    /**
     * {@inheritdoc}
     */
    public function prepareFieldAliases(array $selects)
    {
        $this->resetFieldAliases();

        /** @var Expr\Select $select */
        foreach ($selects as $select) {
            foreach ($select->getParts() as $part) {
                $part = preg_replace('/ as /i', ' as ', $part);
                if (strpos($part, ' as ') !== false) {
                    list($field, $alias) = explode(' as ', $part, 2);
                    $this->fieldAliases[trim($alias)] = trim($field);
                }
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function prepareJoinTablePaths(array $joins)
    {
        $this->resetJoinTablePaths();

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveArrayIterator($joins, \RecursiveArrayIterator::CHILD_ARRAYS_ONLY),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );
        /** @var Expr\Join $join */
        foreach ($iterator as $join) {
            $joinTable = $join->getJoin();
            if (!empty($joinTable)) {
                $this->joinTablePaths[$join->getAlias()] = $joinTable;
            }
        }
    }

    /**
     * Removes unused parameters from query builder
     *
     * @param QueryBuilder $qb
     */
    public function fixUnusedParameters(QueryBuilder $qb)
    {
        $dql = $qb->getDQL();
        $usedParameters = [];
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
    public function dqlContainsParameter($dql, $parameterName)
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
     * @param string $rootAlias
     *
     * @return array
     */
    public function getUsedJoinAliases($joins, $aliases, $rootAlias)
    {
        $incomeAliasesCount = count($aliases);
        /** @var Expr\Join $join */
        foreach ($joins[$rootAlias] as $join) {
            $joinTable = $join->getJoin();
            $joinCondition = $join->getCondition();
            if (!empty($joinTable) && strpos($joinTable, '.') !== false) {
                $data = explode('.', $joinTable);
                if (!in_array($data[0], $aliases)) {
                    $aliases[] = $data[0];
                }
            }
            $aliases = array_merge($aliases, $this->getUsedTableAliases($joinCondition));
        }

        $aliases = array_unique($aliases);
        if ($incomeAliasesCount !== count($aliases)) {
            // resolve joins recursively in order to fetch dependencies between joins
            return $this->getUsedJoinAliases($joins, $aliases, $rootAlias);
        }

        return $aliases;
    }

    /**
     * Get list of table aliases mentioned in condition.
     *
     * @param string|object|array $where
     * @param bool                $replace
     *
     * @return array
     */
    public function getUsedTableAliases($where, $replace = true)
    {
        $aliases = [];

        if (is_array($where)) {
            foreach ($where as $wherePart) {
                $aliases = array_merge($aliases, $this->getUsedTableAliases($wherePart, $replace));
            }
        } else {
            $where = (string) $where;

            if ($replace) {
                $where  = $this->replaceAliasesWithJoinPaths($where);
                $where  = $this->replaceAliasesWithFields($where);
            }
            $fields = $this->getFields($where);
            foreach ($fields as $field) {
                if (strpos($field, '.') !== false) {
                    $data = explode('.', $field, 2);
                    $aliases[] = $data[0];
                }
            }
            $aliases = array_merge($aliases, $this->getUsedAliases($where));
        }

        return array_unique($aliases);
    }

    /**
     * Replaces field aliases with real fields.
     *
     * @param string $condition
     * @return string
     */
    public function replaceAliasesWithFields($condition)
    {
        $condition = (string) $condition;
        foreach ($this->fieldAliases as $alias => $field) {
            $condition = preg_replace($this->getRegExpQueryForAlias($alias), $field, $condition);
        }

        return trim($condition);
    }

    /**
     * Replaces entity aliases with StateFieldPathExpression in WITH|ON conditional statements
     *
     * @param string $condition
     *
     * @return string
     */
    public function replaceAliasesWithJoinPaths($condition)
    {
        $condition = (string) $condition;
        foreach ($this->joinTablePaths as $alias => $field) {
            if (strpos($field, '.') !== false) {
                $condition = preg_replace($this->getRegExpQueryForAlias($alias), $field, $condition);
            }
        }

        return trim($condition);
    }

    /**
     * Get list of aliases used in condition.
     *
     * @param string|object|array $condition
     * @return array
     */
    public function getUsedAliases($condition)
    {
        $aliases = [];
        if (is_array($condition)) {
            foreach ($condition as $conditionPart) {
                $aliases = array_merge($aliases, $this->getUsedAliases($conditionPart));
            }
        } else {
            $condition    = (string)$condition;
            $knownAliases = array_keys(array_merge($this->fieldAliases, $this->joinTablePaths));
            foreach ($knownAliases as $alias) {
                if (preg_match($this->getRegExpQueryForAlias($alias), $condition)) {
                    $aliases[] = $alias;
                }
            }
        }

        return array_unique($aliases);
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
        $searchRegExpParts = [
            '(?<![\w:.])(' . $alias .')(?=[^\.\w]+)',
            '(?<![\w:.])(' . $alias .')$'
        ];

        return '/' . implode('|', $searchRegExpParts) . '/';
    }

    /**
     * Get field mentioned in condition.
     *
     * @param string $condition
     * @return array
     */
    public function getFields($condition)
    {
        $condition = (string) $condition;
        $fields = [];

        preg_match_all('/(\w+\.\w+)/', $condition, $matches);
        if (count($matches) > 1) {
            $fields = array_unique($matches[1]);
        }

        return $fields;
    }

    /**
     * @param string $condition
     * @param array $knownAliases
     * @return array
     */
    public function getTablesUsedInJoinCondition($condition, array $knownAliases)
    {
        if (!$condition) {
            return [];
        }

        $usedAliases = $this->getUsedTableAliases($condition, false);
        foreach ($knownAliases as $alias) {
            preg_match($this->getRegExpQueryForAlias($alias), $condition, $matches);
            if (!empty($matches)) {
                $usedAliases[] = $alias;
            }
        }

        return array_unique($usedAliases);
    }
}
