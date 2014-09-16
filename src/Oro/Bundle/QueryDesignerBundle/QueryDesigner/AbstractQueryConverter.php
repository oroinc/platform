<?php

namespace Oro\Bundle\QueryDesignerBundle\QueryDesigner;

use Oro\Bundle\EntityBundle\Provider\VirtualFieldProviderInterface;
use Oro\Bundle\QueryDesignerBundle\Model\AbstractQueryDesigner;
use Oro\Bundle\QueryDesignerBundle\Exception\InvalidConfigurationException;

/**
 * Provides a core functionality to convert a query definition created by the query designer to another format.
 *
 * @todo: need to think how to reduce the complexity of this class
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
abstract class AbstractQueryConverter
{
    const COLUMN_ALIAS_TEMPLATE = 'c%d';
    const TABLE_ALIAS_TEMPLATE  = 't%d';

    /**
     * @var JoinIdentifierHelper
     */
    protected $joinIdHelper;

    /**
     * @var FunctionProviderInterface
     */
    protected $functionProvider;

    /**
     * @var VirtualFieldProviderInterface
     */
    protected $virtualFieldProvider;

    /**
     * @var string
     */
    private $rootEntity;

    /**
     * @var array
     */
    protected $definition;

    /**
     * @var array
     *      key   = alias
     *      value = joinId
     */
    protected $joins;

    /**
     * @var array
     *      key   = joinId
     *      value = alias
     */
    protected $tableAliases;

    /**
     * @var array
     *      key   = column key (see buildColumnAliasKey method)
     *      value = alias
     */
    protected $columnAliases;

    /**
     * @var array
     *      key   = column name
     *      value = column expression
     */
    protected $virtualColumnExpressions;

    /**
     * @var array
     *      key   = {declared entity class name}::{declared field name}
     *      value = data type
     */
    protected $virtualColumnOptions;

    /**
     * Constructor
     *
     * @param FunctionProviderInterface     $functionProvider
     * @param VirtualFieldProviderInterface $virtualFieldProvider
     */
    protected function __construct(
        FunctionProviderInterface $functionProvider,
        VirtualFieldProviderInterface $virtualFieldProvider
    ) {
        $this->functionProvider     = $functionProvider;
        $this->virtualFieldProvider = $virtualFieldProvider;
    }

    /**
     * Makes sure that a table identified by $joinByFieldName joined
     * on the same level as a table identified by $tableAlias.
     *
     * For example assume that $tableAlias points to
     *      table1::orders -> table2::products
     * and $joinByFieldName is, for example, 'statuses'.
     * In this case the checked join will be
     *      table1::orders -> table2::statuses
     *
     * @param string $tableAlias      The alias of a table to check
     * @param string $joinByFieldName The name of a field should be used to check a join
     *
     * @return string The table alias for the checked join
     */
    public function ensureSiblingTableJoined($tableAlias, $joinByFieldName)
    {
        $joinId       = $this->getJoinIdentifierByTableAlias($tableAlias);
        $parentJoinId = $this->getParentJoinIdentifier($joinId);
        $newJoinId    = $this->buildSiblingJoinIdentifier($parentJoinId, $joinByFieldName);

        return $this->ensureTableJoined($newJoinId);
    }

    /**
     * Makes sure that child table joined by $joinByFieldName joined as a relation of table with $tableAlias alias
     *
     * For example:
     *      table1::orders -> table2::products
     * call of ensureChildTableJoined(table2, stockItem) will check whether following table is joined:
     *      table1::orders -> table2::products -> table2::stockItem
     *
     * @param string      $tableAlias      The alias of a table to check
     * @param string      $joinByFieldName The name of a field should be used to check a join
     * @param null|string $joinType
     *
     * @return string The table alias for the checked join
     */
    public function ensureChildTableJoined($tableAlias, $joinByFieldName, $joinType = null)
    {
        $parentJoinId = $this->getJoinIdentifierByTableAlias($tableAlias);
        $joinId       = $this->joinIdHelper->buildJoinIdentifier(
            $tableAlias . '.' . $joinByFieldName,
            $parentJoinId,
            $joinType
        );

        return $this->ensureTableJoined($joinId);
    }

    /**
     * Makes sure that a table identified by the given $joinId exists in the query
     *
     * @param string $joinId
     * $return string The table alias for the given join
     */
    public function ensureTableJoined($joinId)
    {
        if (!isset($this->tableAliases[$joinId])) {
            $this->addTableAliasesForJoinIdentifier($joinId);
        }

        return $this->tableAliases[$joinId];
    }

    /**
     * Gets join identifier for the given table alias
     *
     * @param $tableAlias
     *
     * @return string
     */
    public function getJoinIdentifierByTableAlias($tableAlias)
    {
        return isset($this->joins[$tableAlias])
            ? $this->joins[$tableAlias]
            : null;
    }

    /**
     * Builds join identifier for a table is joined on the same level as a table identified by $joinId.
     *
     * @param string $joinId          The join identifier
     * @param string $joinByFieldName The name of a field should be used to join new table
     *
     * @return string The join identifier
     */
    public function buildSiblingJoinIdentifier($joinId, $joinByFieldName)
    {
        return $this->joinIdHelper->buildSiblingJoinIdentifier($joinId, $joinByFieldName);
    }

    /**
     * Extracts a parent join identifier
     *
     * @param string $joinId
     *
     * @return string
     * @throws \LogicException if incorrect join identifier specified
     */
    public function getParentJoinIdentifier($joinId)
    {
        return $this->joinIdHelper->getParentJoinIdentifier($joinId);
    }

    /**
     * Converts a query from the query designer format to a target format
     *
     * @param AbstractQueryDesigner $source
     *
     * @throws InvalidConfigurationException
     */
    protected function doConvert(AbstractQueryDesigner $source)
    {
        $this->rootEntity = $source->getEntity();
        $this->definition = json_decode($source->getDefinition(), true);

        if (!isset($this->definition['columns'])) {
            throw new InvalidConfigurationException('The "columns" definition does not exist.');
        }
        if (empty($this->definition['columns'])) {
            throw new InvalidConfigurationException('The "columns" definition must not be empty.');
        }

        $this->joinIdHelper             = new JoinIdentifierHelper($this->rootEntity);
        $this->joins                    = [];
        $this->tableAliases             = [];
        $this->columnAliases            = [];
        $this->virtualColumnExpressions = [];
        $this->virtualColumnOptions     = [];
        $this->buildQuery();
        $this->virtualColumnOptions     = null;
        $this->virtualColumnExpressions = null;
        $this->columnAliases            = null;
        $this->tableAliases             = null;
        $this->joins                    = null;
        $this->joinIdHelper             = null;
    }

    /**
     * A factory method provides an algorithm used to convert a query
     */
    protected function buildQuery()
    {
        $this->prepareTableAliases();
        $this->prepareColumnAliases();

        $this->addSelectStatement();
        $this->addFromStatements();
        $this->addJoinStatements();
        $this->addWhereStatement();
        $this->addGroupByStatement();
        $this->addOrderByStatement();

        $this->saveTableAliases($this->tableAliases);
        $this->saveColumnAliases($this->columnAliases);
    }

    /**
     * Prepares aliases for tables involved to a query
     */
    protected function prepareTableAliases()
    {
        $this->addTableAliasForRootEntity();
        if (isset($this->definition['filters'])) {
            $this->addTableAliasesForFilters($this->definition['filters']);
        }
        foreach ($this->definition['columns'] as $column) {
            $this->addTableAliasesForColumn($column['name']);
        }
        if (isset($this->definition['grouping_columns'])) {
            foreach ($this->definition['grouping_columns'] as $column) {
                $this->addTableAliasesForColumn($column['name']);
            }
        }
    }

    /**
     * Stores all table aliases in the query
     *
     * @param array $tableAliases
     */
    protected function saveTableAliases($tableAliases)
    {
    }

    /**
     * Prepares aliases for columns should be returned by a query
     */
    protected function prepareColumnAliases()
    {
        foreach ($this->definition['columns'] as $column) {
            $this->columnAliases[$this->buildColumnAliasKey($column)] = $this->generateColumnAlias();
        }
    }

    /**
     * Stores all column aliases in the query
     *
     * @param array $columnAliases
     */
    protected function saveColumnAliases($columnAliases)
    {
    }

    /**
     * Performs conversion of SELECT statement
     */
    protected function addSelectStatement()
    {
        foreach ($this->definition['columns'] as $column) {
            $columnName         = $column['name'];
            $fieldName          = $this->getFieldName($columnName);
            $functionExpr       = null;
            $functionReturnType = null;
            if (isset($column['func']) && !empty($column['func'])) {
                $function           = $this->functionProvider->getFunction(
                    $column['func']['name'],
                    $column['func']['group_name'],
                    $column['func']['group_type']
                );
                $functionExpr       = $function['expr'];
                $functionReturnType = isset($function['return_type']) ? $function['return_type'] : null;
            }
            $tableAlias = $this->getTableAliasForColumn($columnName);
            $this->addSelectColumn(
                $this->getEntityClassName($columnName),
                $tableAlias,
                $fieldName,
                $this->buildColumnExpression($columnName, $tableAlias, $fieldName),
                $this->columnAliases[$this->buildColumnAliasKey($column)],
                isset($column['label']) ? $column['label'] : $fieldName,
                $functionExpr,
                $functionReturnType
            );
        }
    }

    /**
     * Performs conversion of a single column of SELECT statement
     *
     * @param string                        $entityClassName
     * @param string                        $tableAlias
     * @param string                        $fieldName
     * @param string                        $columnExpr
     * @param string                        $columnAlias
     * @param string                        $columnLabel
     * @param string|FunctionInterface|null $functionExpr
     * @param string|null                   $functionReturnType
     */
    abstract protected function addSelectColumn(
        $entityClassName,
        $tableAlias,
        $fieldName,
        $columnExpr,
        $columnAlias,
        $columnLabel,
        $functionExpr,
        $functionReturnType
    );

    /**
     * Performs conversion of FROM statement
     */
    protected function addFromStatements()
    {
        $this->addFromStatement($this->rootEntity, $this->tableAliases['']);
    }

    /**
     * Performs conversion of a single table of FROM statement
     *
     * @param string $entityClassName
     * @param string $tableAlias
     */
    abstract protected function addFromStatement($entityClassName, $tableAlias);

    /**
     * Performs conversion of JOIN statements
     */
    protected function addJoinStatements()
    {
        foreach ($this->tableAliases as $joinId => $joinAlias) {
            if (!empty($joinId)) {
                $joinTableAlias = $this->tableAliases[$this->getParentJoinIdentifier($joinId)];

                if ($this->joinIdHelper->isUnidirectionalJoin($joinId)) {
                    $entityClassName = $this->getEntityClassName($joinId);
                    $joinFieldName   = $this->getFieldName($joinId);
                    $this->addJoinStatement(
                        $this->getJoinType($joinId),
                        $entityClassName,
                        $joinAlias,
                        $this->getUnidirectionalJoinConditionType($joinId),
                        $this->getUnidirectionalJoinCondition($joinTableAlias, $joinFieldName, $joinAlias)
                    );
                } elseif ($this->joinIdHelper->isUnidirectionalJoinWithCondition($joinId)) {
                    // such as "Entity:Name|left|WITH|t2.field = t1"

                    $entityClassName = $this->joinIdHelper->getUnidirectionalJoinEntityName($joinId);
                    $this->addJoinStatement(
                        $this->getJoinType($joinId),
                        $entityClassName,
                        $joinAlias,
                        $this->getJoinConditionType($joinId),
                        $this->getJoinCondition($joinId)
                    );
                } else {
                    // bidirectional
                    $join = null === $this->getEntityClassName($joinId)
                        ? $this->getJoin($joinId)
                        : sprintf('%s.%s', $joinTableAlias, $this->getFieldName($joinId));
                    $this->addJoinStatement(
                        $this->getJoinType($joinId),
                        $join,
                        $joinAlias,
                        $this->getJoinConditionType($joinId),
                        $this->getJoinCondition($joinId)
                    );
                }
            }
        }
    }

    /**
     * Returns a string which can be used in a query to get column value
     *
     * @param string $columnName
     * @param string $tableAlias
     * @param string $fieldName
     *
     * @return string
     */
    protected function buildColumnExpression($columnName, $tableAlias, $fieldName)
    {
        return isset($this->virtualColumnExpressions[$columnName])
            ? $this->virtualColumnExpressions[$columnName]
            : sprintf('%s.%s', $tableAlias, $fieldName);
    }

    /**
     * Performs conversion of a single JOIN statement
     *
     * @param string $joinType
     * @param string $join
     * @param string $joinAlias
     * @param string $joinConditionType
     * @param string $joinCondition
     */
    abstract protected function addJoinStatement($joinType, $join, $joinAlias, $joinConditionType, $joinCondition);

    /**
     * Performs conversion of WHERE statement
     */
    protected function addWhereStatement()
    {
        if (isset($this->definition['filters']) && !empty($this->definition['filters'])) {
            $this->processFilters($this->definition['filters'], new FiltersParserContext());
        }
    }

    /**
     * @param array                $filters
     * @param FiltersParserContext $context
     */
    protected function processFilters(array $filters, FiltersParserContext $context)
    {
        $context->checkBeginGroup();
        $this->beginWhereGroup();

        $context->setLastTokenType(FiltersParserContext::BEGIN_GROUP_TOKEN);
        foreach ($filters as $token) {
            if (is_string($token)) {
                $context->checkOperator($token);
                $this->processOperator($token);
                $context->setLastTokenType(FiltersParserContext::OPERATOR_TOKEN);
            } elseif (is_array($token) && isset($token['columnName'])) {
                $context->checkFilter($token);
                $this->processFilter($token);
                $context->setLastTokenType(FiltersParserContext::FILTER_TOKEN);
            } else {
                if (empty($token)) {
                    $context->throwInvalidFiltersException('a group must not be empty');
                }
                $this->processFilters($token, $context);
            }
            $context->setLastToken($token);
        }

        $context->checkEndGroup();
        $this->endWhereGroup();

        $context->setLastTokenType(FiltersParserContext::END_GROUP_TOKEN);
    }

    /**
     * @param string $operator
     */
    protected function processOperator($operator)
    {
        $this->addWhereOperator(strtoupper($operator));
    }

    /**
     * @param array $filter
     */
    protected function processFilter($filter)
    {
        $columnName     = $filter['columnName'];
        $fieldName      = $this->getFieldName($columnName);
        $columnAliasKey = $this->buildColumnAliasKey($columnName);
        $columnAlias    = isset($this->columnAliases[$columnAliasKey]) ? $this->columnAliases[$columnAliasKey] : null;
        $tableAlias     = $this->getTableAliasForColumn($columnName);
        $this->addWhereCondition(
            $this->getEntityClassName($columnName),
            $tableAlias,
            $fieldName,
            $this->buildColumnExpression($columnName, $tableAlias, $fieldName),
            $columnAlias,
            $filter['criterion']['filter'],
            $filter['criterion']['data']
        );
    }

    /**
     * Opens new group in WHERE statement
     */
    abstract protected function beginWhereGroup();

    /**
     * Closes current group in WHERE statement
     */
    abstract protected function endWhereGroup();

    /**
     * Adds an operator to WHERE condition
     *
     * @param string $operator An operator. Can be AND or OR
     */
    abstract protected function addWhereOperator($operator);

    /**
     * Performs conversion of a single WHERE condition
     *
     * @param string $entityClassName
     * @param string $tableAlias
     * @param string $fieldName
     * @param string $columnExpr
     * @param string $columnAlias
     * @param string $filterName
     * @param array  $filterData
     */
    abstract protected function addWhereCondition(
        $entityClassName,
        $tableAlias,
        $fieldName,
        $columnExpr,
        $columnAlias,
        $filterName,
        array $filterData
    );

    /**
     * Performs conversion of GROUP BY statement
     */
    protected function addGroupByStatement()
    {
        if (isset($this->definition['grouping_columns'])) {
            foreach ($this->definition['grouping_columns'] as $column) {
                $columnAliasKey = $this->buildColumnAliasKey($column);
                $columnAlias    = isset($this->columnAliases[$columnAliasKey])
                    ? $this->columnAliases[$columnAliasKey]
                    : null;
                if (empty($columnAlias)) {
                    throw new InvalidConfigurationException(
                        sprintf(
                            'The grouping column "%s" must be declared in SELECT clause.',
                            $column['name']
                        )
                    );
                }
                $this->addGroupByColumn($columnAlias);
            }
        }
    }

    /**
     * Performs conversion of a single column of GROUP BY statement
     *
     * @param string $columnAlias
     */
    abstract protected function addGroupByColumn($columnAlias);

    /**
     * Performs conversion of ORDER BY statement
     */
    protected function addOrderByStatement()
    {
        foreach ($this->definition['columns'] as $column) {
            if (isset($column['sorting']) && $column['sorting'] !== '') {
                $this->addOrderByColumn(
                    $this->columnAliases[$this->buildColumnAliasKey($column)],
                    $column['sorting']
                );
            }
        }
    }

    /**
     * Performs conversion of a single column of ORDER BY statement
     *
     * @param string $columnAlias
     * @param string $columnSorting
     */
    abstract protected function addOrderByColumn($columnAlias, $columnSorting);

    /**
     * Generates and saves an alias for the root entity
     */
    protected function addTableAliasForRootEntity()
    {
        $this->addTableAliasesForJoinIdentifiers(['']);
    }

    /**
     * Generates and saves aliases for the given join identifier and all its parents
     *
     * @param string $joinId
     */
    protected function addTableAliasesForJoinIdentifier($joinId)
    {
        $this->addTableAliasesForJoinIdentifiers(
            $this->joinIdHelper->explodeJoinIdentifier($joinId)
        );
    }

    /**
     * Generates and saves aliases for the given column and all its parent joins
     *
     * @param string $columnName
     */
    protected function addTableAliasesForColumn($columnName)
    {
        $this->addTableAliasesForJoinIdentifiers(
            $this->joinIdHelper->explodeColumnName($columnName)
        );
        $this->addTableAliasesForVirtualColumn($columnName);
    }

    /**
     * Generates and saves table aliases for the given filters
     *
     * @param array $filters
     */
    protected function addTableAliasesForFilters(array $filters)
    {
        foreach ($filters as $item) {
            if (is_array($item)) {
                if (isset($item['columnName'])) {
                    $this->addTableAliasesForColumn($item['columnName']);
                } else {
                    $this->addTableAliasesForFilters($item);
                }
            }
        }
    }

    /**
     * Checks if the given column is the virtual one and if so, generates and saves table aliases for it
     *
     * @param string $columnName
     */
    protected function addTableAliasesForVirtualColumn($columnName)
    {
        if (isset($this->virtualColumnExpressions[$columnName])) {
            // already added
            return;
        }

        $className = $this->getEntityClassName($columnName);
        $fieldName = $this->getFieldName($columnName);
        if (!$this->virtualFieldProvider->isVirtualField($className, $fieldName)) {
            // non virtual column
            return;
        }

        $mainEntityJoinId    = $this->getParentJoinIdentifier(
            $this->joinIdHelper->buildColumnJoinIdentifier($columnName)
        );
        $mainEntityJoinAlias = $this->tableAliases[$mainEntityJoinId];

        $query = $this->virtualFieldProvider->getVirtualFieldQuery($className, $fieldName);
        $joins = [];
        /** @var array $aliasMap
         *      key   = local alias (defined in virtual column query definition)
         *      value = alias
         */
        $aliases = [
            isset($query['root_alias']) ? $query['root_alias'] : 'entity' => $mainEntityJoinAlias
        ];

        if (isset($query['join'])) {
            $this->processVirtualColumnJoins($joins, $aliases, $query, 'inner', $mainEntityJoinId);
            $this->processVirtualColumnJoins($joins, $aliases, $query, 'left', $mainEntityJoinId);
            $this->replaceTableAliasesInVirtualColumnJoinConditions($joins, $aliases);

            foreach ($joins as &$item) {
                $this->registerVirtualColumnTableAlias($joins, $item, $mainEntityJoinId);
            }
        }

        $columnExpr                                  = $this->replaceTableAliasesInVirtualColumnSelect(
            $query['select']['expr'],
            $aliases
        );
        $this->virtualColumnExpressions[$columnName] = $columnExpr;

        $key = sprintf('%s::%s', $className, $fieldName);
        if (!isset($this->virtualColumnOptions[$key])) {
            $options = $query['select'];
            unset($options['expr']);
            $this->virtualColumnOptions[$key] = $options;
        }
    }

    /**
     * Generates and saves aliases for the given joins
     *
     * @param string[] $joinIds
     */
    protected function addTableAliasesForJoinIdentifiers(array $joinIds)
    {
        foreach ($joinIds as $joinId) {
            if (!isset($this->tableAliases[$joinId])) {
                $tableAlias                  = $this->generateTableAlias();
                $this->joins[$tableAlias]    = $joinId;
                $this->tableAliases[$joinId] = $tableAlias;
            }
        }
    }

    /**
     * Saves table alias for the given join which is a part of the virtual column query
     *
     * @param array  $joins
     * @param array  $item
     * @param string $mainEntityJoinId
     *
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    protected function registerVirtualColumnTableAlias(&$joins, $item, $mainEntityJoinId)
    {
        if (isset($item['registered'])) {
            return;
        }
        $parentJoinId = $mainEntityJoinId;

        // TODO should be fixed in scope of BAP-5293
        /*
        $delimiterPos = strpos($item['join'], '.');
        if (false !== $delimiterPos) {
        } else {
            $parentJoinAlias = substr($item['join'], 0, $delimiterPos);
            $parentItem      = null;
            foreach ($joins as &$i) {
                if ($i['alias'] === $parentJoinAlias) {
                    $parentItem = $i;
                    break;
                }
            }
            if (null !== $parentItem && !isset($parentItem['registered'])) {
                $this->registerVirtualColumnTableAlias($joins, $parentItem, $mainEntityJoinId);
            }
            $parentJoinId = $this->joins[$parentJoinAlias];
        }*/
        if (!isset($item['registered'])) {
            $tableAlias                  = $item['alias'];
            $joinId                      = $this->joinIdHelper->buildJoinIdentifier(
                $item['join'],
                $parentJoinId,
                $item['type'],
                isset($item['conditionType']) ? $item['conditionType'] : null,
                isset($item['condition']) ? $item['condition'] : null
            );
            $this->joins[$tableAlias]    = $joinId;
            $this->tableAliases[$joinId] = $tableAlias;
            $item['registered']          = true;
        }
    }

    /**
     * Processes all virtual column join declarations of $joinType type
     *
     * @param array  $joins
     * @param array  $aliases
     * @param array  $query
     * @param string $joinType
     * @param        $parentJoinId
     *
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    protected function processVirtualColumnJoins(&$joins, &$aliases, &$query, $joinType, $parentJoinId)
    {
        if (isset($query['join'][$joinType])) {
            foreach ($query['join'][$joinType] as $item) {
                $item['type'] = $joinType;
                $delimiterPos = strpos($item['join'], '.');
                if (false !== $delimiterPos) {
                    $alias = substr($item['join'], 0, $delimiterPos);
                    if (!isset($aliases[$alias])) {
                        $aliases[$alias] = $this->generateTableAlias();
                    }
                    $item['join'] = $aliases[$alias] . substr($item['join'], $delimiterPos);
                }

                $alias = $item['alias'];
                if (!isset($aliases[$alias])) {
                    $aliases[$alias] = $this->generateTableAlias();
                }
                $item['alias'] = $aliases[$alias];

                $itemJoinId = $this->joinIdHelper->buildJoinIdentifier(
                    $item['join'],
                    $parentJoinId,
                    $item['type'],
                    isset($item['conditionType']) ? $item['conditionType'] : null,
                    isset($item['condition']) ? $item['condition'] : null
                );

                if (isset($this->tableAliases[$itemJoinId])) {
                    $item['alias']   = $this->tableAliases[$itemJoinId];
                    $aliases[$alias] = $this->tableAliases[$itemJoinId];
                }

                $joins[] = $item;
            }
        }
    }

    /**
     * Replaces all table aliases declared in the virtual column query with unique aliases for built query
     *
     * @param array $joins
     * @param array $aliases
     */
    protected function replaceTableAliasesInVirtualColumnJoinConditions(&$joins, &$aliases)
    {
        // replace alias with {{newAlias}} - this is required to prevent collisions
        // between old and new aliases in case if some new alias has the same name as some old alias
        foreach ($joins as &$item) {
            if (isset($item['condition'])) {
                $condition = $item['condition'];
                foreach ($aliases as $alias => $newAlias) {
                    $tryFind = true;
                    while ($tryFind) {
                        $tryFind = false;
                        $pos     = $this->checkTableAliasInCondition($condition, $alias);
                        if (false !== $pos) {
                            $condition = sprintf(
                                '%s{{%s}}%s',
                                substr($condition, 0, $pos),
                                $newAlias,
                                substr($condition, $pos + strlen($alias))
                            );
                            $tryFind   = true;
                        }
                    }
                }
                $item['condition'] = $condition;
            }
        }
        // replace {{newAlias}} with newAlias
        foreach ($joins as &$item) {
            if (isset($item['condition'])) {
                $condition = $item['condition'];
                foreach ($aliases as $newAlias) {
                    $condition = str_replace(sprintf('{{%s}}', $newAlias), $newAlias, $condition);
                }
                $item['condition'] = $condition;
            }
        }
    }

    /**
     * Replaces all table aliases declared in the virtual column select expression with unique aliases for built query
     *
     * @param string $selectExpr
     * @param array  $aliases
     *
     * @return string The corrected select expression
     */
    protected function replaceTableAliasesInVirtualColumnSelect($selectExpr, &$aliases)
    {
        // replace alias with {{newAlias}} - this is required to prevent collisions
        // between old and new aliases in case if some new alias has the same name as some old alias
        foreach ($aliases as $alias => $newAlias) {
            $tryFind = true;
            while ($tryFind) {
                $tryFind = false;
                $pos     = $this->checkTableAliasInSelect($selectExpr, $alias);
                if (false !== $pos) {
                    $selectExpr = sprintf(
                        '%s{{%s}}%s',
                        substr($selectExpr, 0, $pos),
                        $newAlias,
                        substr($selectExpr, $pos + strlen($alias))
                    );
                    $tryFind    = true;
                }
            }
        }
        // replace {{newAlias}} with newAlias
        foreach ($aliases as $newAlias) {
            $selectExpr = str_replace(sprintf('{{%s}}', $newAlias), $newAlias, $selectExpr);
        }

        return $selectExpr;
    }

    /**
     * Checks if $selectExpr contains the given table alias
     *
     * @param string $selectExpr
     * @param string $alias
     *
     * @return bool|int The position of $alias in selectExpr or FALSE if it was not found
     */
    protected function checkTableAliasInSelect($selectExpr, $alias)
    {
        $pos = strpos($selectExpr, $alias);
        while (false !== $pos) {
            if (0 === $pos) {
                $nextChar = substr($selectExpr, $pos + strlen($alias), 1);
                if ('.' === $nextChar) {
                    return $pos;
                }
            } elseif (strlen($selectExpr) !== $pos + strlen($alias) + 1) {
                $prevChar = substr($selectExpr, $pos - 1, 1);
                if (in_array($prevChar, [' ', '(', ','])) {
                    $nextChar = substr($selectExpr, $pos + strlen($alias), 1);
                    if ('.' === $nextChar) {
                        return $pos;
                    }
                }
            }
            $pos = strpos($selectExpr, $alias, $pos + strlen($alias));
        }

        return false;
    }

    /**
     * Checks if $condition contains the given table alias
     *
     * @param string $condition
     * @param string $alias
     *
     * @return bool|int The position of $alias in $condition or FALSE if it was not found
     */
    protected function checkTableAliasInCondition($condition, $alias)
    {
        $pos = strpos($condition, $alias);
        if (false !== $pos) {
            if (0 === $pos) {
                // handle case "ALIAS.", "ALIAS.field"
                $nextChar = substr($condition, $pos + strlen($alias), 1);
                if (in_array($nextChar, ['.', ' ', '='])) {
                    return $pos;
                }
            } elseif (strlen($condition) === $pos + strlen($alias)) {
                // handle case "t2.someField = ALIAS"
                $prevChar = substr($condition, $pos - 1, 1);
                if (in_array($prevChar, [' ', '='])) {
                    return $pos;
                }
            } else {
                // handle case "t2.someField = ALIAS.id"
                $prevChar = substr($condition, $pos - 1, 1);
                if (in_array($prevChar, [' ', '=', '('])) {
                    $nextChar = substr($condition, $pos + strlen($alias), 1);
                    if (in_array($nextChar, ['.', ' ', '=', ')'])) {
                        return $pos;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Gets a root entity of this query
     *
     * @return string
     */
    protected function getRootEntity()
    {
        return $this->rootEntity;
    }

    /**
     * Extracts an entity class name for the given column or from the given join identifier
     *
     * @param string $columnNameOrJoinId
     *
     * @return string
     */
    protected function getEntityClassName($columnNameOrJoinId)
    {
        return $this->joinIdHelper->getEntityClassName($columnNameOrJoinId);
    }

    /**
     * Extracts a field name for the given column or from the given join identifier
     *
     * @param string $columnNameOrJoinId
     *
     * @return string
     */
    protected function getFieldName($columnNameOrJoinId)
    {
        return $this->joinIdHelper->getFieldName($columnNameOrJoinId);
    }

    /**
     * Gets a field data type
     *
     * @param string $className
     * @param string $fieldName
     *
     * @return string
     */
    protected function getFieldType($className, $fieldName)
    {
        $result = null;
        if ($this->virtualFieldProvider->isVirtualField($className, $fieldName)) {
            // try to guess virtual column type
            $key = sprintf('%s::%s', $className, $fieldName);
            if (isset($this->virtualColumnOptions[$key]['return_type'])) {
                $result = $this->virtualColumnOptions[$key]['return_type'];
            }
        }

        return $result;
    }

    /**
     * Gets join part of the given join identifier
     *
     * @param string $joinId
     *
     * @return string
     */
    protected function getJoin($joinId)
    {
        return $this->joinIdHelper->getJoin($joinId);
    }

    /**
     * Gets join type for the given join identifier
     *
     * @param string $joinId
     *
     * @return null|string NULL for autodetect, or a string represents the join type, for example 'inner' or 'left'
     */
    protected function getJoinType($joinId)
    {
        return $this->joinIdHelper->getJoinType($joinId);
    }

    /**
     * Gets the join condition type for the given join identifier
     *
     * @param string $joinId
     *
     * @return string
     */
    protected function getJoinConditionType($joinId)
    {
        return $this->joinIdHelper->getJoinConditionType($joinId);
    }

    /**
     * Gets the join condition type for the given join identifier
     *
     * @param string $joinId
     *
     * @return string
     */
    protected function getUnidirectionalJoinConditionType($joinId)
    {
        return 'WITH';
    }

    /**
     * Gets the join condition the given join identifier
     *
     * @param string $joinId
     *
     * @return null|string
     */
    protected function getJoinCondition($joinId)
    {
        return $this->joinIdHelper->getJoinCondition($joinId);
    }

    /**
     * Gets the join condition the given join identifier
     *
     * @param string $joinTableAlias
     * @param string $joinFieldName
     * @param string $joinAlias
     *
     * @return string
     */
    protected function getUnidirectionalJoinCondition($joinTableAlias, $joinFieldName, $joinAlias)
    {
        return sprintf('%s.%s = %s', $joinAlias, $joinFieldName, $joinTableAlias);
    }

    /**
     * Generates new table alias
     *
     * @return string
     */
    protected function generateTableAlias()
    {
        return sprintf(static::TABLE_ALIAS_TEMPLATE, count($this->tableAliases) + 1);
    }

    /**
     * Generates new column alias
     *
     * @return string
     */
    protected function generateColumnAlias()
    {
        return sprintf(static::COLUMN_ALIAS_TEMPLATE, count($this->columnAliases) + 1);
    }

    /**
     * Returns a table alias for the given column
     *
     * @param string $columnName
     *
     * @return string
     */
    protected function getTableAliasForColumn($columnName)
    {
        $parentJoinId = $this->getParentJoinIdentifier(
            $this->joinIdHelper->buildColumnJoinIdentifier($columnName)
        );

        return $this->tableAliases[$parentJoinId];
    }

    /**
     * Builds a string which is used as a key of column aliases array
     *
     * @param array|string $column The column definition or name
     *
     * @return string
     */
    protected function buildColumnAliasKey($column)
    {
        if (is_string($column)) {
            return $column;
        }

        $result = $column['name'];
        if (isset($column['func']) && !empty($column['func'])) {
            $result = sprintf(
                '%s(%s,%s,%s)',
                $result,
                $column['func']['name'],
                $column['func']['group_name'],
                $column['func']['group_type']
            );
        }

        return $result;
    }

    /**
     * Prepares the given function expression to use in a query
     *
     * @param string|FunctionInterface $functionExpr
     * @param string                   $tableAlias
     * @param string                   $fieldName
     * @param string                   $columnName
     * @param string                   $columnAlias
     *
     * @return string
     * @throws InvalidConfigurationException if incorrect type $functionExpr specified
     */
    protected function prepareFunctionExpression($functionExpr, $tableAlias, $fieldName, $columnName, $columnAlias)
    {
        if (is_string($functionExpr) && strpos($functionExpr, '@') === 0) {
            $className    = substr($functionExpr, 1);
            $functionExpr = new $className();
        }
        if ($functionExpr instanceof FunctionInterface) {
            return $functionExpr->getExpression($tableAlias, $fieldName, $columnName, $columnAlias, $this);
        } elseif (!is_string($functionExpr)) {
            throw new InvalidConfigurationException(
                'The function expression must be a string or instance of FunctionInterface'
            );
        }

        $variables = [
            'column'       => $columnName,
            'column_name'  => $fieldName,
            'column_alias' => $columnAlias,
            'table_alias'  => $tableAlias
        ];

        return preg_replace_callback(
            '/\$([\w_]+)/',
            function ($matches) use (&$variables) {
                return $variables[$matches[1]];
            },
            $functionExpr
        );
    }
}
