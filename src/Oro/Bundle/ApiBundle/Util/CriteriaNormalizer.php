<?php

namespace Oro\Bundle\ApiBundle\Util;

use Oro\Bundle\ApiBundle\Collection\Criteria;
use Oro\Bundle\ApiBundle\Collection\Join;

class CriteriaNormalizer
{
    const JOIN_ALIAS_TEMPLATE = 'alias%d';

    /**
     * @param Criteria $criteria
     */
    public function normalizeCriteria(Criteria $criteria)
    {
        $this->ensureJoinAliasesSet($criteria);
        $this->completeJoins($criteria);
        $this->optimizeJoins($criteria);
    }

    /**
     * Sets missing join aliases
     *
     * @param Criteria $criteria
     */
    protected function ensureJoinAliasesSet(Criteria $criteria)
    {
        $counter = 0;
        $joins = $criteria->getJoins();
        foreach ($joins as $join) {
            $counter++;
            if (!$join->getAlias()) {
                $join->setAlias(sprintf(self::JOIN_ALIAS_TEMPLATE, $counter));
            }
        }
    }

    /**
     * Makes sure that this criteria object contains all required joins and aliases are set for all joins.
     *
     * @param Criteria $criteria
     */
    protected function completeJoins(Criteria $criteria)
    {
        $pathMap = $this->getJoinPathMap($criteria);
        if (!empty($pathMap)) {
            $aliases = [];
            $joins = $criteria->getJoins();
            foreach ($joins as $join) {
                $aliases[] = $join->getAlias();
            }

            $this->sortJoinPathMap($pathMap);
            foreach ($pathMap as $path => $item) {
                if (!$criteria->hasJoin($path)) {
                    $parentPath = $item['parentPath'];
                    $parentAlias = $parentPath
                        ? $criteria->getJoin($parentPath)->getAlias()
                        : Criteria::ROOT_ALIAS_PLACEHOLDER;

                    $alias = $item['field'];
                    $count = 0;
                    while (in_array($alias, $aliases, true)) {
                        $alias = sprintf('%s%d', $item['field'], ++$count);
                    }
                    $aliases[] = $alias;

                    $criteria
                        ->addLeftJoin($path, $parentAlias . '.' . $item['field'])
                        ->setAlias($alias);
                }
            }
        }
    }

    /**
     * Replaces LEFT JOIN with INNER JOIN where it is possible
     *
     * @param Criteria $criteria
     */
    protected function optimizeJoins(Criteria $criteria)
    {
        $fields = $this->getWhereFields($criteria);
        foreach ($fields as $field) {
            $lastDelimiter = strrpos($field, '.');
            while (false !== $lastDelimiter) {
                $field = substr($field, 0, $lastDelimiter);
                $lastDelimiter = false;
                $join = $criteria->getJoin($field);
                if (null !== $join && Join::LEFT_JOIN === $join->getJoinType()) {
                    $join->setJoinType(Join::INNER_JOIN);
                    $lastDelimiter = strrpos($field, '.');
                }
            }
        }
    }

    /**
     * @param Criteria $criteria
     *
     * @return array
     *  [
     *      path => [
     *          'field' => string,
     *          'parentPath' => string|null,
     *          'nestingLevel' => integer
     *      ],
     *      ...
     *  ]
     */
    protected function getJoinPathMap(Criteria $criteria)
    {
        $pathMap = [];

        $joinPaths = array_keys($criteria->getJoins());
        foreach ($joinPaths as $path) {
            $pathMap[$path] = $this->buildJoinPathMapValue($path);
        }

        $rootPath = substr(Criteria::ROOT_ALIAS_PLACEHOLDER, 1, -1);
        $fields = $this->getFields($criteria);
        foreach ($fields as $field) {
            while ($field) {
                $path = $this->getPath($field, $rootPath);
                $field = null;
                if ($path && Criteria::ROOT_ALIAS_PLACEHOLDER !== $path && !isset($pathMap[$path])) {
                    $pathMap[$path] = $this->buildJoinPathMapValue($path);
                    $field = $path;
                }
            }
        }

        return $pathMap;
    }

    /**
     * @param string $field
     * @param string $rootPath
     *
     * @return string|null
     */
    protected function getPath($field, $rootPath)
    {
        $path = null;
        if (0 === strpos($field, '{')) {
            if ('}' === substr($field, -1)) {
                $path = substr($field, 1, -2);
                if ($rootPath === $path) {
                    $path = null;
                }
            } else {
                $lastDelimiter = strrpos($field, '.');
                if (false !== $lastDelimiter && '}' === substr($field, $lastDelimiter - 1, 1)) {
                    $path = substr($field, 1, $lastDelimiter - 2);
                    if ($rootPath === $path) {
                        $path = null;
                    }
                }
            }
        } else {
            $lastDelimiter = strrpos($field, '.');
            if (false !== $lastDelimiter) {
                $path = substr($field, 0, $lastDelimiter);
            }
        }

        return $path;
    }

    /**
     * @param string $path
     *
     * @return array
     */
    protected function buildJoinPathMapValue($path)
    {
        $lastDelimiter = strrpos($path, '.');
        if (false === $lastDelimiter) {
            return [
                'field'        => $path,
                'parentPath'   => null,
                'nestingLevel' => 0

            ];
        }

        $parentPath = substr($path, 0, $lastDelimiter);

        return [
            'field'        => substr($path, $lastDelimiter + 1),
            'parentPath'   => $parentPath,
            'nestingLevel' => count(explode('.', $parentPath))
        ];
    }

    /**
     * @param Criteria $criteria
     *
     * @return string[]
     */
    protected function getWhereFields(Criteria $criteria)
    {
        $whereExpr = $criteria->getWhereExpression();
        if (!$whereExpr) {
            return [];
        }

        $visitor = new FieldVisitor();
        $visitor->dispatch($whereExpr);

        return $visitor->getFields();
    }

    /**
     * @param Criteria $criteria
     *
     * @return string[]
     */
    protected function getFields(Criteria $criteria)
    {
        $fields = $this->getWhereFields($criteria);

        $orderBy = $criteria->getOrderings();
        foreach ($orderBy as $field => $direction) {
            if (!in_array($field, $fields, true)) {
                $fields[] = $field;
            }
        }

        return $fields;
    }

    /**
     * @param array $pathMap
     */
    protected function sortJoinPathMap(array &$pathMap)
    {
        uasort(
            $pathMap,
            function (array $a, array $b) {
                if ($a['nestingLevel'] === $b['nestingLevel']) {
                    return 0;
                } elseif ($a['nestingLevel'] < $b['nestingLevel']) {
                    return -1;
                } else {
                    return 1;
                }
            }
        );
    }
}
