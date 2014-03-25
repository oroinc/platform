<?php

namespace Oro\Bundle\BatchBundle\ORM\QueryBuilder;

use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;

class QueryBuilderTools
{
    /**
     * @var array
     */
    protected $fieldAliases = array();

    /**
     * @param array $selects
     */
    public function __construct(array $selects = null)
    {
        if ($selects) {
            $this->prepareFieldAliases($selects);
        }
    }

    /**
     * Get field by alias.
     *
     * @param string $alias
     * @return null|string
     */
    public function getFieldByAlias($alias)
    {
        if (isset($this->fieldAliases[$alias])) {
            return $this->fieldAliases[$alias];
        }

        return null;
    }

    /**
     * Reset field aliases.
     */
    public function resetFieldAliases()
    {
        $this->fieldAliases = array();
    }

    /**
     * Get field aliases.
     *
     * @return array
     */
    public function getFieldAliases()
    {
        return $this->fieldAliases;
    }

    /**
     * Get mapping of filed aliases to real field expressions.
     *
     * @param array $selects
     * @return array
     */
    public function prepareFieldAliases($selects)
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
     * Removes unused parameters from query builder
     *
     * @param QueryBuilder $qb
     */
    public function fixUnusedParameters(QueryBuilder $qb)
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
     * @param $rootAlias
     * @return array
     */
    public function getUsedJoinAliases($joins, $aliases, $rootAlias)
    {
        /** @var Expr\Join $join */
        foreach ($joins[$rootAlias] as $join) {
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

        return array_unique($aliases);
    }

    /**
     * Get list of table aliases mentioned in condition.
     *
     * @param string|object|array $where
     * @return array
     */
    public function getUsedTableAliases($where)
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
                $fields = $this->getFields($where);
                foreach ($fields as $field) {
                    if (strpos($field, '.') !== false) {
                        $data = explode('.', $field, 2);
                        $aliases[] = $data[0];
                    }
                }
            }
        }

        return array_unique($aliases);
    }

    /**
     * Replace field aliases with real fields.
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
     * Get list of aliases used in condition.
     *
     * @param string|object|array $condition
     * @return array
     */
    public function getUsedAliases($condition)
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
        $searchRegExpParts = array(
            '(?<![\w:.])(' . $alias .')(?=\W)',
            '(?<![\w:.])(' . $alias .')$'
        );

        return '/' . implode('|', $searchRegExpParts) . '/';
    }

    /**
     * Workaround for http://www.doctrine-project.org/jira/browse/DDC-1858
     *
     * @param string|object $having
     * @return mixed|string
     */
    public function fixHavingAliases($having)
    {
        $having = (string) $having;
        foreach ($this->fieldAliases as $alias => $field) {
            $having = preg_replace('/(?<![\w:])(' . $alias . '\s*LIKE)/', $field . ' LIKE', $having);
            $having = preg_replace('/(?<![\w:])(' . $alias . '\s*NOT\s+LIKE)/', $field . ' NOT LIKE', $having);
            $having = preg_replace('/(?<![\w:])(' . $alias . '\s*IS\s+NULL)/', $field . ' IS NULL', $having);
            $having = preg_replace('/(?<![\w:])(' . $alias . '\s*IS\s+NOT\s+NULL)/', $field . ' IS NOT NULL', $having);
        }

        return $having;
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
        $fields = array();

        preg_match_all('/(\w+\.\w+)/', $condition, $matches);
        if (count($matches) > 1) {
            $fields = array_unique($matches[1]);
        }

        return $fields;
    }
}
