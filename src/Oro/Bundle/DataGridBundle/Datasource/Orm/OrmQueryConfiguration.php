<?php

namespace Oro\Bundle\DataGridBundle\Datasource\Orm;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class OrmQueryConfiguration
{
    const DISTINCT_PATH   = '[source][query][distinct]';
    const SELECT_PATH     = '[source][query][select]';
    const FROM_PATH       = '[source][query][from]';
    const JOIN_PATH       = '[source][query][join]';
    const INNER_JOIN_PATH = '[source][query][join][inner]';
    const LEFT_JOIN_PATH  = '[source][query][join][left]';
    const WHERE_PATH      = '[source][query][where]';
    const WHERE_AND_PATH  = '[source][query][where][and]';
    const WHERE_OR_PATH   = '[source][query][where][or]';
    const HAVING_PATH     = '[source][query][having]';
    const GROUP_BY_PATH   = '[source][query][groupBy]';
    const ORDER_BY_PATH   = '[source][query][orderBy]';
    const HINTS_PATH      = '[source][hints]';

    const TABLE_KEY          = 'table';
    const ALIAS_KEY          = 'alias';
    const JOIN_KEY           = 'join';
    const CONDITION_TYPE_KEY = 'conditionType';
    const CONDITION_KEY      = 'condition';
    const COLUMN_KEY         = 'column';
    const DIRECTION_KEY      = 'dir';
    const NAME_KEY           = 'name';
    const VALUE_KEY          = 'value';

    const GENERATED_JOIN_ALIAS_TEMPLATE = 'auto_rel_%d';

    /** @var DatagridConfiguration */
    private $config;

    /** @var array */
    private $generatedJoinAliases = [];

    /**
     * @param DatagridConfiguration $config
     */
    public function __construct(DatagridConfiguration $config)
    {
        $this->config = $config;
    }

    /**
     * Gets a DISTINCT flag to the query.
     *
     * @return bool
     */
    public function getDistinct()
    {
        return (bool)$this->config->offsetGetByPath(self::DISTINCT_PATH, false);
    }

    /**
     * Sets a DISTINCT flag to the query.
     *
     * @param bool $distinct
     *
     * @return self
     */
    public function setDistinct($distinct = true)
    {
        if ($distinct) {
            $this->config->offsetSetByPath(self::DISTINCT_PATH, true);
        } else {
            $this->config->offsetUnsetByPath(self::DISTINCT_PATH);
        }

        return $this;
    }

    /**
     * Gets SELECT part of the query.
     *
     * @return string[]
     */
    public function getSelect()
    {
        return $this->config->offsetGetByPath(self::SELECT_PATH, []);
    }

    /**
     * Sets SELECT part of the query.
     *
     * @param string[] $select
     *
     * @return self
     */
    public function setSelect(array $select)
    {
        $this->config->offsetSetByPath(self::SELECT_PATH, $select);

        return $this;
    }

    /**
     * Removes SELECT part of the query.
     *
     * @return self
     */
    public function resetSelect()
    {
        $this->config->offsetUnsetByPath(self::SELECT_PATH);

        return $this;
    }

    /**
     * Adds an item to SELECT part of the query.
     *
     * @param mixed $select
     *
     * @return self
     */
    public function addSelect($select)
    {
        if (is_array($select)) {
            $this->config->offsetSetByPath(
                self::SELECT_PATH,
                array_merge($this->config->offsetGetByPath(self::SELECT_PATH, []), $select)
            );
        } else {
            $this->config->offsetAddToArrayByPath(self::SELECT_PATH, [$select]);
        }

        return $this;
    }

    /**
     * Gets the FIRST root alias of the query.
     *
     * @return string|null
     */
    public function getRootAlias()
    {
        $fromPart = $this->getFrom();
        if (empty($fromPart)) {
            return null;
        }
        $from = reset($fromPart);

        return array_key_exists(self::ALIAS_KEY, $from)
            ? $from[self::ALIAS_KEY]
            : null;
    }

    /**
     * Gets the FIRST root entity of the query.
     *
     * @param EntityClassResolver|null $entityClassResolver
     * @param bool                     $lookAtExtendedEntityClassName
     *
     * @return string|null
     */
    public function getRootEntity(
        EntityClassResolver $entityClassResolver = null,
        $lookAtExtendedEntityClassName = false
    ) {
        if ($lookAtExtendedEntityClassName) {
            $entityClassName = $this->config->getExtendedEntityClassName();
            if ($entityClassName) {
                return $entityClassName;
            }
        }

        $fromPart = $this->getFrom();
        if (empty($fromPart)) {
            return null;
        }

        $entity = null;
        $from = reset($fromPart);
        if (!empty($from[self::TABLE_KEY])) {
            $entity = $from[self::TABLE_KEY];
            if (null !== $entityClassResolver) {
                $entity = $entityClassResolver->getEntityClass($entity);
            }
        }

        return $entity;
    }

    /**
     * Gets the root alias for the given entity.
     *
     * @param string                   $entityClass
     * @param EntityClassResolver|null $entityClassResolver
     *
     * @return string|null
     */
    public function findRootAlias($entityClass, EntityClassResolver $entityClassResolver = null)
    {
        $entity = null;
        $fromPart = $this->getFrom();
        foreach ($fromPart as $from) {
            $currentEntityClass = $from[self::TABLE_KEY];
            if (null !== $entityClassResolver) {
                $currentEntityClass = $entityClassResolver->getEntityClass($currentEntityClass);
            }
            if ($currentEntityClass === $entityClass) {
                $entity = $from[self::ALIAS_KEY];
            }
        }

        return $entity;
    }

    /**
     * Gets an alias for the given join.
     * If the query does not contain the specified join, its alias will be generated automatically.
     * This might be helpful if you need to get an alias to extended association that will be
     * joined later.
     *
     * @param string      $join          The relationship to join.
     * @param string|null $conditionType The condition type constant. Either "ON" or "WITH".
     * @param string|null $condition     The condition for the join.
     *
     * @return string
     */
    public function getJoinAlias($join, $conditionType = null, $condition = null)
    {
        $joinAlias = $this->findJoinAlias($join, $conditionType, $condition);
        if (!$joinAlias) {
            foreach ($this->generatedJoinAliases as $item) {
                if ($this->isJoinEqual($item, $join, $conditionType, $condition)) {
                    $joinAlias = $item[self::ALIAS_KEY];
                    break;
                }
            }
            if (!$joinAlias) {
                $joinAlias = sprintf(self::GENERATED_JOIN_ALIAS_TEMPLATE, count($this->generatedJoinAliases) + 1);
                $this->generatedJoinAliases[] = $this->buildJoin($join, $joinAlias, $conditionType, $condition);
            }
        }

        return $joinAlias;
    }

    /**
     * Converts an association based join to a subquery.
     * This can be helpful in case of performance issues with a datagrid.
     *
     * For example, the following method
     * <code>
     *  $query->convertAssociationJoinToSubquery('g', 'groupName', 'AcmeBundle:UserGroup');
     * </code>
     * converts the query
     * <code>
     *  query:
     *      select:
     *          - g.name as groupName
     *      from:
     *          - { table: AcmeBundle:User, alias: u }
     *      join:
     *          left:
     *              - { join: u.group, alias: g }
     * </code>
     * to
     * <code>
     *  query:
     *      select:
     *          - (SELECT g.name FROM AcmeBundle:UserGroup AS g WHERE g = u.group) as groupName
     *      from:
     *          - { table: AcmeBundle:User, alias: u }
     * </code>
     *
     * @param string $joinAlias
     * @param string $columnAlias
     * @param string $joinEntityClass
     */
    public function convertAssociationJoinToSubquery($joinAlias, $columnAlias, $joinEntityClass)
    {
        list(
            $join,
            $joinPath,
            $selectExpr,
            $selectPath
            ) = $this->findJoinAndSelectByAliases($joinAlias, $columnAlias);
        if (!$join || !$selectExpr) {
            return;
        }

        $subQuery = sprintf(
            'SELECT %1$s FROM %4$s AS %3$s WHERE %3$s = %2$s',
            $selectExpr,
            $join[self::JOIN_KEY],
            $joinAlias,
            $joinEntityClass
        );
        if (!empty($join[self::CONDITION_KEY])) {
            $subQuery .= sprintf(' AND %s', $join[self::CONDITION_KEY]);
        }

        $this->config->offsetSetByPath($selectPath, sprintf('(%s) AS %s', $subQuery, $columnAlias));
        $this->config->offsetUnsetByPath($joinPath);
    }

    /**
     * Converts an entity based join to a subquery.
     * This can be helpful in case of performance issues with a datagrid.
     *
     * For example, the following method
     * <code>
     *  $query->convertEntityJoinToSubquery('g', 'groupName');
     * </code>
     * converts the query
     * <code>
     *  query:
     *      select:
     *          - g.name as groupName
     *      from:
     *          - { table: AcmeBundle:User, alias: u }
     *      join:
     *          left:
     *              - { join: AcmeBundle:UserGroup, alias: g, conditionType: WITH, condition: g = u.group }
     * </code>
     * to
     * <code>
     *  query:
     *      select:
     *          - (SELECT g.name FROM AcmeBundle:UserGroup AS g WHERE g = u.group) as groupName
     *      from:
     *          - { table: AcmeBundle:User, alias: u }
     * </code>
     *
     * @param string $joinAlias
     * @param string $columnAlias
     */
    public function convertEntityJoinToSubquery($joinAlias, $columnAlias)
    {
        list(
            $join,
            $joinPath,
            $selectExpr,
            $selectPath
            ) = $this->findJoinAndSelectByAliases($joinAlias, $columnAlias);
        if (!$join || !$selectExpr || empty($join[self::CONDITION_KEY])) {
            return;
        }

        $subQuery = sprintf(
            'SELECT %s FROM %s AS %s WHERE %s',
            $selectExpr,
            $join[self::JOIN_KEY],
            $joinAlias,
            $join[self::CONDITION_KEY]
        );
        $this->config->offsetSetByPath($selectPath, sprintf('(%s) AS %s', $subQuery, $columnAlias));
        $this->config->offsetUnsetByPath($joinPath);
    }

    /**
     * Gets FROM part of the query.
     *
     * @return array [['table' => entity class name, 'alias' => entity alias], ...]
     */
    public function getFrom()
    {
        return $this->config->offsetGetByPath(self::FROM_PATH, []);
    }

    /**
     * Sets FROM part of the query.
     *
     * @param array $from [['table' => entity class name, 'alias' => entity alias], ...]
     *
     * @return self
     */
    public function setFrom(array $from)
    {
        $this->config->offsetSetByPath(self::FROM_PATH, $from);

        return $this;
    }

    /**
     * Removes FROM part of the query.
     *
     * @return self
     */
    public function resetFrom()
    {
        $this->config->offsetUnsetByPath(self::FROM_PATH);

        return $this;
    }

    /**
     * Adds an item to FROM part of the query.
     *
     * @param string $from
     * @param string $alias
     *
     * @return self
     */
    public function addFrom($from, $alias)
    {
        $this->config->offsetAddToArrayByPath(
            self::FROM_PATH,
            [[self::TABLE_KEY => $from, self::ALIAS_KEY => $alias]]
        );

        return $this;
    }

    /**
     * Gets all INNER joins of the query.
     *
     * @return array
     */
    public function getInnerJoins()
    {
        return $this->config->offsetGetByPath(self::INNER_JOIN_PATH, []);
    }

    /**
     * Gets all LEFT joins of the query.
     *
     * @return array
     */
    public function getLeftJoins()
    {
        return $this->config->offsetGetByPath(self::LEFT_JOIN_PATH, []);
    }

    /**
     * Sets INNER joins of the query.
     *
     * @param array $joins
     *
     * @return self
     */
    public function setInnerJoins(array $joins)
    {
        $this->config->offsetSetByPath(self::INNER_JOIN_PATH, $joins);

        return $this;
    }

    /**
     * Sets LEFT joins of the query.
     *
     * @param array $joins
     *
     * @return self
     */
    public function setLeftJoins(array $joins)
    {
        $this->config->offsetSetByPath(self::LEFT_JOIN_PATH, $joins);

        return $this;
    }

    /**
     * Adds INNER join to the query.
     *
     * @param string      $join          The relationship to join.
     * @param string      $alias         The alias of the join.
     * @param string|null $conditionType The condition type constant. Either "ON" or "WITH".
     * @param string|null $condition     The condition for the join.
     *
     * @return self
     */
    public function addInnerJoin($join, $alias, $conditionType = null, $condition = null)
    {
        $this->config->offsetAddToArrayByPath(
            self::INNER_JOIN_PATH,
            [$this->buildJoin($join, $alias, $conditionType, $condition)]
        );

        return $this;
    }

    /**
     * Adds LEFT join to the query.
     *
     * @param string      $join          The relationship to join.
     * @param string      $alias         The alias of the join.
     * @param string|null $conditionType The condition type constant. Either "ON" or "WITH".
     * @param string|null $condition     The condition for the join.
     *
     * @return self
     */
    public function addLeftJoin($join, $alias, $conditionType = null, $condition = null)
    {
        $this->config->offsetAddToArrayByPath(
            self::LEFT_JOIN_PATH,
            [$this->buildJoin($join, $alias, $conditionType, $condition)]
        );

        return $this;
    }

    /**
     * Gets WHERE part of the query.
     *
     * @return array
     */
    public function getWhere()
    {
        return $this->config->offsetGetByPath(self::WHERE_PATH, []);
    }

    /**
     * Sets WHERE part of the query.
     *
     * @param array $where
     *
     * @return self
     */
    public function setWhere(array $where)
    {
        $this->config->offsetSetByPath(self::WHERE_PATH, $where);

        return $this;
    }

    /**
     * Removes WHERE part of the query.
     *
     * @return self
     */
    public function resetWhere()
    {
        $this->config->offsetUnsetByPath(self::WHERE_PATH);

        return $this;
    }

    /**
     * Adds one or more restrictions to WHERE part of the query, forming a logical
     * conjunction (AND operator) with any previously specified restrictions.
     *
     * @param mixed $where
     *
     * @return self
     */
    public function addAndWhere($where)
    {
        if (!is_array($where)) {
            $where = [$where];
        }
        $this->config->offsetAddToArrayByPath(self::WHERE_AND_PATH, $where);

        return $this;
    }

    /**
     * Adds one or more restrictions to WHERE part of the query, forming a logical
     * disjunction (OR operator) with any previously specified restrictions.
     *
     * @param mixed $where
     *
     * @return self
     */
    public function addOrWhere($where)
    {
        if (!is_array($where)) {
            $where = [$where];
        }
        $this->config->offsetAddToArrayByPath(self::WHERE_OR_PATH, $where);

        return $this;
    }

    /**
     * Gets HAVING part of the query.
     *
     * @return string|null
     */
    public function getHaving()
    {
        return $this->config->offsetGetByPath(self::HAVING_PATH);
    }

    /**
     * Sets HAVING part of the query.
     *
     * @param string $having
     *
     * @return self
     */
    public function setHaving($having)
    {
        $this->config->offsetSetByPath(self::HAVING_PATH, $having);

        return $this;
    }

    /**
     * Removes HAVING part of the query.
     *
     * @return self
     */
    public function resetHaving()
    {
        $this->config->offsetUnsetByPath(self::HAVING_PATH);

        return $this;
    }

    /**
     * Adds an item to HAVING part of the query.
     *
     * @param string $having
     *
     * @return self
     */
    public function addHaving($having)
    {
        $existingHaving = $this->getHaving();
        if ($existingHaving) {
            $having = $existingHaving . ',' . $having;
        }

        return $this->setHaving($having);
    }

    /**
     * Gets GROUP BY part of the query.
     *
     * @return string|null
     */
    public function getGroupBy()
    {
        return $this->config->offsetGetByPath(self::GROUP_BY_PATH);
    }

    /**
     * Sets GROUP BY part of the query.
     *
     * @param string $groupBy
     *
     * @return self
     */
    public function setGroupBy($groupBy)
    {
        $this->config->offsetSetByPath(self::GROUP_BY_PATH, $groupBy);

        return $this;
    }

    /**
     * Removes GROUP BY part of the query.
     *
     * @return self
     */
    public function resetGroupBy()
    {
        $this->config->offsetUnsetByPath(self::GROUP_BY_PATH);

        return $this;
    }

    /**
     * Adds an item to GROUP BY part of the query.
     *
     * @param string $groupBy
     *
     * @return self
     */
    public function addGroupBy($groupBy)
    {
        $existingGroupBy = $this->getGroupBy();
        if ($existingGroupBy) {
            $groupBy = $existingGroupBy . ',' . $groupBy;
        }

        return $this->setGroupBy($groupBy);
    }

    /**
     * Gets ORDER BY part of the query.
     *
     * @return array
     */
    public function getOrderBy()
    {
        return $this->config->offsetGetByPath(self::ORDER_BY_PATH, []);
    }

    /**
     * Sets ORDER BY part of the query.
     *
     * @param array $orderBy
     *
     * @return self
     */
    public function setOrderBy(array $orderBy)
    {
        $this->config->offsetSetByPath(self::ORDER_BY_PATH, $orderBy);

        return $this;
    }

    /**
     * Removes ORDER BY part of the query.
     *
     * @return self
     */
    public function resetOrderBy()
    {
        $this->config->offsetUnsetByPath(self::ORDER_BY_PATH);

        return $this;
    }

    /**
     * Adds an item to ORDER BY part of the query.
     *
     * @param string $column    The name of a column.
     * @param mixed  $direction The sorting direction. Either "asc" or "desc".
     *
     * @return self
     */
    public function addOrderBy($column, $direction = 'asc')
    {
        $this->config->offsetAddToArrayByPath(
            self::ORDER_BY_PATH,
            [[self::COLUMN_KEY => $column, self::DIRECTION_KEY => $direction]]
        );

        return $this;
    }

    /**
     * Gets the query hints.
     *
     * @return array
     */
    public function getHints()
    {
        return $this->config->offsetGetByPath(self::HINTS_PATH, []);
    }

    /**
     * Sets the query hints.
     *
     * @param array $hints
     *
     * @return self
     */
    public function setHints(array $hints)
    {
        $this->config->offsetSetByPath(self::HINTS_PATH, $hints);

        return $this;
    }

    /**
     * Removes the query hints.
     *
     * @return self
     */
    public function resetHints()
    {
        $this->config->offsetUnsetByPath(self::HINTS_PATH);

        return $this;
    }

    /**
     * Adds a hint to the query.
     *
     * @param string $name  The name of a hint.
     * @param mixed  $value The value of a hint.
     *
     * @return self
     */
    public function addHint($name, $value = null)
    {
        if ($value) {
            $this->config->offsetAddToArrayByPath(
                self::HINTS_PATH,
                [[self::NAME_KEY => $name, self::VALUE_KEY => $value]]
            );
        } else {
            $this->config->offsetAddToArrayByPath(self::HINTS_PATH, [$name]);
        }

        return $this;
    }

    /**
     * @param string      $join
     * @param string      $alias
     * @param string|null $conditionType
     * @param string|null $condition
     *
     * @return array
     */
    private function buildJoin($join, $alias, $conditionType = null, $condition = null)
    {
        $result = [self::JOIN_KEY => $join, self::ALIAS_KEY => $alias];
        if ($conditionType) {
            $result[self::CONDITION_TYPE_KEY] = $conditionType;
        }
        if ($condition) {
            $result[self::CONDITION_KEY] = $condition;
        }

        return $result;
    }

    /**
     * @param string      $join
     * @param string|null $conditionType
     * @param string|null $condition
     *
     * @return string|null
     */
    private function findJoinAlias($join, $conditionType = null, $condition = null)
    {
        $joinAlias = null;

        $allJoins = $this->config->offsetGetByPath(self::JOIN_PATH, []);
        foreach ($allJoins as $joins) {
            foreach ($joins as $item) {
                if ($this->isJoinEqual($item, $join, $conditionType, $condition)) {
                    $joinAlias = $item[self::ALIAS_KEY];
                    break;
                }
            }
        }

        return $joinAlias;
    }

    /**
     * @param array       $item
     * @param string      $join
     * @param string|null $conditionType
     * @param string|null $condition
     *
     * @return bool
     */
    private function isJoinEqual(array $item, $join, $conditionType = null, $condition = null)
    {
        if (!$this->isJoinAttributeEqual($item, self::JOIN_KEY, $join)) {
            return false;
        }
        if ($conditionType && !$this->isJoinAttributeEqual($item, self::CONDITION_TYPE_KEY, $conditionType)) {
            return false;
        }
        if ($condition && !$this->isJoinAttributeEqual($item, self::CONDITION_KEY, $condition)) {
            return false;
        }

        return true;
    }

    /**
     * @param array  $item
     * @param string $attributeName
     * @param mixed  $attributeValue
     *
     * @return bool
     */
    private function isJoinAttributeEqual(array $item, $attributeName, $attributeValue)
    {
        return
            array_key_exists($attributeName, $item)
            && $attributeValue === $item[$attributeName];
    }

    /**
     * @param string $joinAlias
     * @param string $joinsPath
     *
     * @return array [join, join path]
     */
    private function findJoinByAlias($joinAlias, $joinsPath)
    {
        $foundJoin = null;
        $foundJoinPath = null;
        $joins = $this->config->offsetGetByPath($joinsPath, []);
        foreach ($joins as $key => $join) {
            if ($join[self::ALIAS_KEY] === $joinAlias) {
                $foundJoin = $join;
                $foundJoinPath = sprintf('%s[%s]', $joinsPath, $key);
                break;
            }
        }

        return [$foundJoin, $foundJoinPath];
    }

    /**
     * @param string $columnAlias
     *
     * @return array [select expression without column alias, select item path]
     */
    private function findSelectExprByAlias($columnAlias)
    {
        $foundSelectExpr = null;
        $foundSelectPath = null;
        $pattern = sprintf('#(?P<expr>.+?)\\s+AS\\s+%s#i', $columnAlias);
        $selects = $this->config->offsetGetByPath(self::SELECT_PATH, []);
        foreach ($selects as $key => $select) {
            if (preg_match($pattern, $select, $matches)) {
                $foundSelectExpr = $matches['expr'];
                $foundSelectPath = sprintf('%s[%s]', self::SELECT_PATH, $key);
                break;
            }
        }

        return [$foundSelectExpr, $foundSelectPath];
    }

    /**
     * @param string $joinAlias
     * @param string $columnAlias
     *
     * @return array [join, join path, select expression without column alias, select item path]
     */
    private function findJoinAndSelectByAliases($joinAlias, $columnAlias)
    {
        list($join, $joinPath) = $this->findJoinByAlias($joinAlias, self::INNER_JOIN_PATH);
        if (null === $join) {
            list($join, $joinPath) = $this->findJoinByAlias($joinAlias, self::LEFT_JOIN_PATH);
        }
        $selectExpr = null;
        $selectPath = null;
        if (null !== $join) {
            list($selectExpr, $selectPath) = $this->findSelectExprByAlias($columnAlias);
        }

        return [$join, $joinPath, $selectExpr, $selectPath];
    }
}
