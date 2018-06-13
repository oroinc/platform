<?php

namespace Oro\Bundle\QueryDesignerBundle\QueryDesigner;

use Oro\Bundle\BatchBundle\ORM\QueryBuilder\QueryBuilderTools;
use Oro\Bundle\EntityBundle\Provider\VirtualFieldProviderInterface;
use Oro\Bundle\EntityBundle\Provider\VirtualRelationProviderInterface;
use Oro\Bundle\QueryDesignerBundle\Exception\InvalidConfigurationException;
use Oro\Bundle\QueryDesignerBundle\Model\AbstractQueryDesigner;

/**
 * Provides a core functionality to convert a query definition created by the query designer to another format.
 *
 * @todo: need to think how to reduce the complexity of this class
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
abstract class AbstractQueryConverter
{
    const COLUMN_ALIAS_TEMPLATE = 'c%d';
    const TABLE_ALIAS_TEMPLATE  = 't%d';
    const ROOT_ALIAS_KEY = '';
    const MAX_ITERATIONS = 100;

    const INNER_JOIN = 'inner';
    const LEFT_JOIN  = 'left';

    const CONDITIONAL_JOIN = 'WITH';

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
     * @var VirtualRelationProviderInterface
     */
    protected $virtualRelationProvider;

    /**
     * @var int
     */
    protected $tableAliasesCount = 0;

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
     * @var array
     */
    protected $virtualRelationsJoins = [];

    /**
     * @var array
     */
    protected $aliases = [];

    /**
     * @var array
     */
    protected $queryAliases = [];

    /**
     * @var QueryBuilderTools
     */
    protected $qbTools;

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
        $this->qbTools = new QueryBuilderTools();
    }

    /**
     * @param VirtualRelationProviderInterface $virtualRelationProvider
     */
    public function setVirtualRelationProvider(VirtualRelationProviderInterface $virtualRelationProvider)
    {
        $this->virtualRelationProvider = $virtualRelationProvider;
    }

    /**
     * Stores all table aliases in the query
     *
     * @param array $tableAliases
     */
    abstract protected function saveTableAliases($tableAliases);

    /**
     * Stores all column aliases in the query
     *
     * @param array $columnAliases
     */
    abstract protected function saveColumnAliases($columnAliases);

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
     * @param bool                          $isDistinct
     *
     * @return
     */
    abstract protected function addSelectColumn(
        $entityClassName,
        $tableAlias,
        $fieldName,
        $columnExpr,
        $columnAlias,
        $columnLabel,
        $functionExpr,
        $functionReturnType,
        $isDistinct = false
    );

    /**
     * Performs conversion of a single table of FROM statement
     *
     * @param string $entityClassName
     * @param string $tableAlias
     */
    abstract protected function addFromStatement($entityClassName, $tableAlias);

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
     * @param string                        $entityClassName
     * @param string                        $tableAlias
     * @param string                        $fieldName
     * @param string                        $columnExpr
     * @param string                        $columnAlias
     * @param string                        $filterName
     * @param array                         $filterData
     * @param string|FunctionInterface|null $functionExpr
     */
    abstract protected function addWhereCondition(
        $entityClassName,
        $tableAlias,
        $fieldName,
        $columnExpr,
        $columnAlias,
        $filterName,
        array $filterData,
        $functionExpr = null
    );

    /**
     * Performs conversion of a single column of GROUP BY statement
     *
     * @param string $columnAlias
     */
    abstract protected function addGroupByColumn($columnAlias);

    /**
     * Performs conversion of a single column of ORDER BY statement
     *
     * @param string $columnAlias
     * @param string $columnSorting
     */
    abstract protected function addOrderByColumn($columnAlias, $columnSorting);

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
     * @param string|null $joinType
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
     * @return string The table alias for the given join
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
     * @param string $tableAlias
     *
     * @return string|null
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

        $this->aliases                  = [];
        $this->virtualRelationsJoins    = [];
        $this->tableAliasesCount        = 0;
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
     * Prepares aliases for columns should be returned by a query
     */
    protected function prepareColumnAliases()
    {
        foreach ($this->definition['columns'] as $column) {
            $this->columnAliases[$this->buildColumnAliasKey($column)] = $this->generateColumnAlias();
        }
    }

    /**
     * @param array $column
     *
     * @return array Where array has elements: string|FunctionInterface|null, string|null
     */
    protected function createColumnFunction(array $column)
    {
        if (!empty($column['func'])) {
            $function = $this->functionProvider->getFunction(
                $column['func']['name'],
                $column['func']['group_name'],
                $column['func']['group_type']
            );

            $functionExpr       = $function['expr'];
            $functionReturnType = isset($function['return_type'])
                ? $function['return_type']
                : null;

            return [$functionExpr, $functionReturnType];
        }

        return [null, null];
    }

    /**
     * Performs conversion of SELECT statement
     */
    protected function addSelectStatement()
    {
        foreach ($this->definition['columns'] as $column) {
            $columnName         = $column['name'];
            $fieldName          = $this->getFieldName($columnName);
            list($functionExpr, $functionReturnType) = $this->createColumnFunction($column);
            $isDistinct = !empty($column['distinct']);
            $tableAlias = $this->getTableAliasForColumn($columnName);
            $columnLabel = isset($column['label'])
                ? $column['label']
                : $fieldName;
            $this->addSelectColumn(
                $this->getEntityClassName($columnName),
                $tableAlias,
                $fieldName,
                $this->buildColumnExpression($columnName, $tableAlias, $fieldName),
                $this->getColumnAlias($this->buildColumnAliasKey($column)),
                $columnLabel,
                $functionExpr,
                $functionReturnType,
                $isDistinct
            );
        }
    }

    /**
     * @param string $columnAliasKey
     * @return null|string
     */
    protected function getColumnAlias($columnAliasKey)
    {
        return isset($this->columnAliases[$columnAliasKey])
            ? $this->columnAliases[$columnAliasKey]
            : null;
    }

    /**
     * Performs conversion of FROM statement
     */
    protected function addFromStatements()
    {
        $this->addFromStatement($this->rootEntity, $this->tableAliases[self::ROOT_ALIAS_KEY]);
    }

    /**
     * Performs conversion of JOIN statements
     */
    protected function addJoinStatements()
    {
        foreach ($this->tableAliases as $joinId => $joinAlias) {
            if (!empty($joinId)) {
                $parentJoinId = $this->getParentJoinIdentifier($joinId);
                $joinTableAlias = $this->tableAliases[$parentJoinId];

                $virtualRelation = array_search($parentJoinId, $this->virtualRelationsJoins);
                if (false !== $virtualRelation) {
                    $className = $this->getEntityClassName($virtualRelation);
                    $fieldName = $this->getFieldName($virtualRelation);

                    $joinTableAlias = $this->aliases[$this->virtualRelationProvider->getTargetJoinAlias(
                        $className,
                        $fieldName,
                        $this->getFieldName($joinId)
                    )];
                }

                if ($this->joinIdHelper->isUnidirectionalJoin($joinId)) {
                    $entityClassName = $this->getEntityClassName($joinId);
                    $joinFieldName   = $this->getFieldName($joinId);
                    $this->addJoinStatement(
                        $this->getJoinType($joinId),
                        $entityClassName,
                        $joinAlias,
                        self::CONDITIONAL_JOIN,
                        $this->getUnidirectionalJoinCondition(
                            $joinTableAlias,
                            $joinFieldName,
                            $joinAlias,
                            $entityClassName
                        )
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
                    if (null === $this->getEntityClassName($joinId)) {
                        $join = $this->getJoin($joinId);
                    } else {
                        $join = sprintf('%s.%s', $joinTableAlias, $this->getFieldName($joinId));
                    }
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
     * Performs conversion of WHERE statement
     */
    protected function addWhereStatement()
    {
        if (!empty($this->definition['filters'])) {
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
            } elseif (is_array($token) && isset($token['criterion'])) {
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
        $columnName     = array_key_exists('columnName', $filter) ? $filter['columnName'] : '';
        $fieldName      = $this->getFieldName($columnName);
        $columnAliasKey = $this->buildColumnAliasKey($columnName);
        $tableAlias     = $this->getTableAliasForColumn($columnName);
        $column = ['name' => $fieldName];
        if (isset($filter['func'])) {
            $column['func'] = $filter['func'];
        }
        list($functionExpr) = $this->createColumnFunction($column);

        $this->addWhereCondition(
            $this->getEntityClassName($columnName),
            $tableAlias,
            $fieldName,
            $this->buildColumnExpression($columnName, $tableAlias, $fieldName),
            $this->getColumnAlias($columnAliasKey),
            $filter['criterion']['filter'],
            $filter['criterion']['data'],
            $functionExpr
        );
    }

    /**
     * Performs conversion of GROUP BY statement
     */
    protected function addGroupByStatement()
    {
        if (isset($this->definition['grouping_columns'])) {
            foreach ($this->definition['grouping_columns'] as $column) {
                $columnAliasKey = $this->buildColumnAliasKey($column);
                $columnAlias    = $this->getColumnAlias($columnAliasKey);
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
     * Performs conversion of ORDER BY statement
     */
    protected function addOrderByStatement()
    {
        foreach ($this->definition['columns'] as $column) {
            if (!empty($column['sorting'])) {
                $this->addOrderByColumn(
                    $this->getColumnAlias($this->buildColumnAliasKey($column)),
                    $column['sorting']
                );
            }
        }
    }

    /**
     * Generates and saves an alias for the root entity
     */
    protected function addTableAliasForRootEntity()
    {
        $this->registerTableAlias(self::ROOT_ALIAS_KEY);
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
     * @param string $columnName String with specified format
     *                           rootEntityField+Class\Name::joinedEntityRelation+Relation\Class::fieldToSelect
     */
    protected function addTableAliasesForColumn($columnName)
    {
        $this->addTableAliasesForVirtualRelation($columnName);
        $this->addTableAliasesForVirtualField($columnName);
    }

    /**
     * Generates and saves table aliases for the given filters
     *
     * @param array $filters
     */
    protected function addTableAliasesForFilters(array $filters)
    {
        foreach ($filters as $filter) {
            if (is_array($filter)) {
                if (isset($filter['columnName'])) {
                    $this->addTableAliasesForColumn($filter['columnName']);
                } else {
                    $this->addTableAliasesForFilters($filter);
                }
            }
        }
    }

    /**
     * Checks if the given column is a virtual field and if so, generates and saves table aliases for it
     *
     * @param string $columnName
     */
    protected function addTableAliasesForVirtualField($columnName)
    {
        if (isset($this->virtualColumnExpressions[$columnName])) {
            // already processed
            return;
        }

        $className = $this->getEntityClassName($columnName);
        $fieldName = $this->getFieldName($columnName);
        if (!$className || !$this->virtualFieldProvider->isVirtualField($className, $fieldName)) {
            // not a virtual field
            return;
        }

        $mainEntityJoinId = $this->getParentJoinIdentifier(
            $this->joinIdHelper->buildColumnJoinIdentifier($columnName)
        );

        $query = $this->registerVirtualColumnQueryAliases(
            $this->virtualFieldProvider->getVirtualFieldQuery($className, $fieldName),
            $mainEntityJoinId
        );

        $this->virtualColumnExpressions[$columnName] = $query['select']['expr'];
        $key = sprintf('%s::%s', $className, $fieldName);
        if (!isset($this->virtualColumnOptions[$key])) {
            $options = $query['select'];
            unset($options['expr']);
            $this->virtualColumnOptions[$key] = $options;
        }
    }

    /**
     * Checks if the given column is a virtual field and if so, generates and saves table aliases for it
     *
     * @param string $columnName
     */
    protected function addTableAliasesForVirtualFieldWithParentJoinId($columnName, $mainEntityJoinId)
    {
        if (isset($this->virtualColumnExpressions[$columnName])) {
            // already processed
            return;
        }
        $className = $this->getEntityClassName($columnName);
        $fieldName = $this->getFieldName($columnName);
        if (!$className || !$this->virtualFieldProvider->isVirtualField($className, $fieldName)) {
            // not a virtual field
            return;
        }
        $query = $this->registerVirtualColumnQueryAliases(
            $this->virtualFieldProvider->getVirtualFieldQuery($className, $fieldName),
            $mainEntityJoinId
        );
        $this->virtualColumnExpressions[$columnName] = $query['select']['expr'];
        $key = sprintf('%s::%s', $className, $fieldName);
        if (!isset($this->virtualColumnOptions[$key])) {
            $options = $query['select'];
            unset($options['expr']);
            $this->virtualColumnOptions[$key] = $options;
        }
    }

    /**
     * @param string $joinId
     *
     * @return string
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function replaceJoinsForVirtualRelation($joinId)
    {
        if (!$this->virtualRelationProvider) {
            return $joinId;
        }

        /**
         * mainEntityJoinId - parent join definition
         *
         * For `Root\Class::rootEntityField+Class\Name::joinedEntityRelation` parent is `Root\Class::rootEntityField`
         */
        $mainEntityJoinId = self::ROOT_ALIAS_KEY;

        /**
         * columnJoinIds - array of joins path
         *
         * For `Root\Class::rootEntityField+Class\Name::joinedEntityRelation` will be
         *
         * - `Root\Class::rootEntityField`
         * - `Class\Name::joinedEntityRelation`
         */
        $columnJoinIds = explode('+', $joinId);

        /**
         * Walk over $columnJoinIds and replace virtual relations joins using query configuration
         */
        foreach ($columnJoinIds as &$columnJoinId) {
            /**
             * Check existing join definition. Full definition stored
             *
             * Relation - `Class\Name::joinedEntityRelation`
             * Relation Join - `Root\Class::rootEntityField+Join\Class::someField+Rel\Class|left|WITH|alias.code = 1`
             *
             * mainEntityJoinId contains full definition for next iteration - Relation Join
             * columnJoinId will be replaced with `Join\Class::someField+Rel\Class|left|WITH|alias.code = 1`
             */
            if (!empty($this->virtualRelationsJoins[$columnJoinId])) {
                $columnJoinId = trim(
                    str_replace($mainEntityJoinId, '', $this->virtualRelationsJoins[$columnJoinId]),
                    '+'
                );
                $relationColumnJoinIds = explode('+', $columnJoinId);
                $fullRelationColumnJoinId = self::ROOT_ALIAS_KEY;
                foreach ($relationColumnJoinIds as $relationColumnJoinId) {
                    $mainEntityJoinId = trim($mainEntityJoinId . '+' . $relationColumnJoinId, '+');
                    $fullRelationColumnJoinId = trim($fullRelationColumnJoinId . '+' . $relationColumnJoinId, '+');
                    $tableAlias = null;
                    if (!empty($this->tableAliases[$fullRelationColumnJoinId])) {
                        $tableAlias = $this->tableAliases[$fullRelationColumnJoinId];
                    }

                    $this->registerTableAlias($mainEntityJoinId, $tableAlias);
                }

                continue;
            }

            $className = $this->getEntityClassName($columnJoinId);
            $fieldName = $this->getFieldName($columnJoinId);

            if (!$this->virtualRelationProvider->isVirtualRelation($className, $fieldName)) {
                /**
                 * Was joined previously in virtual relation
                 */
                if (!empty($this->aliases[$fieldName])) {
                    $columnJoinId = null;
                    continue;
                }

                /**
                 * For non virtual join we register aliases with replaced virtual relations joins in path
                 */
                $mainEntityJoinId = trim($mainEntityJoinId . '+' . $columnJoinId, '+');
                $this->registerTableAlias($mainEntityJoinId);

                continue;
            }

            $query = $this->virtualRelationProvider->getVirtualRelationQuery($className, $fieldName);
            $mainEntityJoinAlias = $this->tableAliases[$mainEntityJoinId];

            $this->prepareAliases($query, $mainEntityJoinAlias);

            /**
             * Get virtual joins definitions according to aliased dependencies
             *
             * idx => [
             *      join => Join\Class
             *      alias => t2
             *      conditionType => WITH
             *      condition => alias.code = 1
             * ]
             */
            $joins = $this->buildVirtualJoins($query, $mainEntityJoinId);

            $this->replaceTableAliasesInVirtualColumnJoinConditions($joins);

            /**
             * Store mainEntityJoinId to build columnJoinId after virtual relations joins build
             *
             * `Root\Class::rootEntityField`
             */
            $baseMainEntityJoinId = $mainEntityJoinId;
            $virtualJoinId = self::ROOT_ALIAS_KEY;
            foreach ($joins as $join) {
                $tableAlias = $join['alias'];

                /**
                 * Build virtual relation join including parent one and register it
                 *
                 * For joins:
                 *
                 * `Join\Class::someField' => `Root\Class::rootEntityField+Join\Class::someField`
                 * `Rel\Class|left|WITH|alias.code = 1`
                 *      => `Root\Class::rootEntityField+Join\Class::someField+Rel\Class|left|WITH|alias.code = 1`
                 */
                $virtualJoinId = $this->buildVirtualColumnJoinIdentifier($joins, $join, $mainEntityJoinId);

                $this->registerTableAlias($virtualJoinId, $tableAlias);
                $mainEntityJoinId = $virtualJoinId;
            }

            /**
             * Store join built definition
             *
             * `Class\Name::joinedEntityRelation`
             *      => `Root\Class::rootEntityField+Join\Class::someField+Rel\Class|left|WITH|alias.code = 1`
             */
            $this->virtualRelationsJoins[$columnJoinId] = $virtualJoinId;

            /**
             * Replace columnJoinId with virtual relation join with its built definition
             * Class\Name::joinedEntityRelation` => `Join\Class::someField+Rel\Class|left|WITH|alias.code = 1`
             */
            $columnJoinId = trim(str_replace($baseMainEntityJoinId, '', $mainEntityJoinId), '+');
        }

        /**
         * Join columnJoinIds back into path. All virtual relation joins replaced with joins according to query
         * definition
         */
        return implode('+', array_filter($columnJoinIds));
    }

    /**
     * @param array  $query
     * @param string $mainEntityJoinAlias
     */
    protected function prepareAliases(array $query, $mainEntityJoinAlias)
    {
        $this->aliases[$this->getQueryRootAlias($query)] = $mainEntityJoinAlias;
    }

    /**
     * @param array $query
     *
     * @return string
     */
    protected function getQueryRootAlias(array $query)
    {
        return isset($query['root_alias'])
            ? $query['root_alias']
            : 'entity';
    }

    /**
     * @param array  $query
     * @param string $mainEntityJoinId
     *
     * @return array
     */
    protected function buildVirtualJoins(array $query, $mainEntityJoinId)
    {
        $joins = [];
        $iterations = 0;

        $this->buildQueryAliases($query);

        do {
            $this->processVirtualColumnJoins($joins, $query, self::INNER_JOIN, $mainEntityJoinId);
            $this->processVirtualColumnJoins($joins, $query, self::LEFT_JOIN, $mainEntityJoinId);

            if ($iterations > self::MAX_ITERATIONS) {
                throw new \RuntimeException(
                    'Could not reorder joins correctly. Number of tries has exceeded maximum allowed.'
                );
            }

            $iterations++;
        } while (count($this->aliases) != count($this->queryAliases));

        $this->queryAliases = [];

        return $joins;
    }

    /**
     * @param string      $joinId
     * @param string|null $tableAlias
     */
    protected function registerTableAlias($joinId, $tableAlias = null)
    {
        if (!isset($this->tableAliases[$joinId])) {
            if (!$tableAlias) {
                $tableAlias = $this->generateTableAlias();
            }

            $this->tableAliases[$joinId] = $tableAlias;
            $this->joins[$tableAlias] = $joinId;
        }
    }

    /**
     * Checks if the given column is a virtual relation and if so, generates and saves table aliases for it
     *
     * @param string $columnName
     */
    protected function addTableAliasesForVirtualRelation($columnName)
    {
        if (!empty($this->virtualColumnExpressions[$columnName])) {
            // already processed
            return;
        }

        $joinIds = $this->addTableAliasesForJoinIdentifiers(
            $this->joinIdHelper->explodeColumnName($columnName)
        );

        if (!$this->virtualRelationsJoins) {
            return;
        }

        $hasVirtualRelation = false;
        foreach ($joinIds as $columnJoinId) {
            $hasVirtualRelation = $hasVirtualRelation || array_search($columnJoinId, $this->virtualRelationsJoins);
        }

        if (!$hasVirtualRelation) {
            return;
        }

        // check if last field is virtual, if yes, add tableAlias to related virtual field. check BAP-15414 and OPS-637
        $isLastVirtualField = $this->virtualFieldProvider->isVirtualField(
            $this->getEntityClassName($columnName),
            $this->getFieldName($columnName)
        );
        if ($isLastVirtualField) {
            $this->addTableAliasesForVirtualFieldWithParentJoinId($columnName, $columnJoinId);
            return;
        }

        $parentJoinId = $this->getParentJoinIdentifier(
            $this->joinIdHelper->buildColumnJoinIdentifier($columnName)
        );
        $fieldName = $this->getFieldName($parentJoinId);
        $className = $this->getEntityClassName($parentJoinId);

        if ($this->virtualRelationProvider->isVirtualRelation($className, $fieldName)) {
            $tableAlias = $this->aliases[$this->virtualRelationProvider->getTargetJoinAlias(
                $className,
                $fieldName,
                $this->getFieldName($columnName)
            )];
        } else {
            $joinId = end($joinIds);
            $tableAlias = $this->tableAliases[$joinId];
        }

        $this->virtualColumnExpressions[$columnName] = sprintf('%s.%s', $tableAlias, $this->getFieldName($columnName));
    }

    /**
     * Generates and saves aliases for the given joins
     *
     * @param string[] $joinIds
     *
     * @return string[] updated ids of joins
     */
    protected function addTableAliasesForJoinIdentifiers(array $joinIds)
    {
        $result = [];
        foreach ($joinIds as $joinId) {
            $joinId = $this->replaceJoinsForVirtualRelation($joinId);
            $this->registerTableAlias($joinId);
            $result[] = $joinId;
        }

        return $result;
    }

    /**
     * Saves table alias for the given join which is a part of the virtual column query
     *
     * @param array  $joins
     * @param array  $join
     * @param string $mainEntityJoinId
     */
    protected function registerVirtualColumnTableAlias($joins, $join, $mainEntityJoinId)
    {
        $tableAlias = $join['alias'];
        if (!empty($this->joins[$tableAlias])) {
            return;
        }

        $joinId = $this->buildVirtualColumnJoinIdentifier($joins, $join, $mainEntityJoinId);

        $this->registerTableAlias($joinId, $tableAlias);
    }

    /**
     * @param array  $joins
     * @param array  $join
     * @param string $mainEntityJoinId
     *
     * @return string
     */
    protected function buildVirtualColumnJoinIdentifier($joins, $join, $mainEntityJoinId)
    {
        $parentJoinId = $mainEntityJoinId;

        $delimiterPos = strpos($join['join'], '.');
        if (false !== $delimiterPos) {
            $parentJoinAlias = substr($join['join'], 0, $delimiterPos);
            $parentItems = array_filter(
                $joins,
                function ($join) use ($parentJoinAlias) {
                    return $join['alias'] === $parentJoinAlias;
                }
            );
            $parentItem = reset($parentItems);
            $parentAlias = $parentItem['alias'];
            if ($parentItem && empty($this->joins[$parentAlias])) {
                $this->registerVirtualColumnTableAlias($joins, $parentItem, $mainEntityJoinId);
            }
            if (!empty($this->joins[$parentJoinAlias])) {
                $parentJoinId = $this->joins[$parentJoinAlias];
            }
        }

        return $this->buildJoinIdentifier($join, $parentJoinId);
    }

    /**
     * @param array $query
     */
    protected function buildQueryAliases(array $query)
    {
        $queryAliases = array_keys($this->aliases);

        foreach ([self::INNER_JOIN, self::LEFT_JOIN] as $type) {
            if (empty($query['join'][$type])) {
                continue;
            }

            foreach ($query['join'][$type] as $join) {
                $queryAliases[] = $join['alias'];
            }
        }

        $this->queryAliases = array_unique($queryAliases);
    }

    /**
     * Processes all virtual column join declarations of $joinType type
     *
     * @param array  $joins
     * @param array  $query
     * @param string $joinType
     * @param string $parentJoinId
     */
    protected function processVirtualColumnJoins(&$joins, &$query, $joinType, $parentJoinId)
    {
        if (!isset($query['join'][$joinType])) {
            return;
        }

        foreach ($query['join'][$joinType] as &$join) {
            if (!empty($join['processed'])) {
                continue;
            }

            $usedAliases = $this->qbTools->getTablesUsedInJoinCondition(
                $this->getDefinitionJoinCondition($join),
                $this->queryAliases
            );
            $unknownAliases = array_diff(
                $usedAliases,
                array_merge(array_keys($this->aliases), [$join['alias']])
            );
            if ($unknownAliases) {
                continue;
            }

            $join['type'] = $joinType;
            $delimiterPos = strpos($join['join'], '.');
            if (false !== $delimiterPos) {
                $alias = substr($join['join'], 0, $delimiterPos);
                if (!isset($this->aliases[$alias])) {
                    $this->aliases[$alias] = $this->generateTableAlias();
                }
                $join['join'] = $this->aliases[$alias] . substr($join['join'], $delimiterPos);
            }

            $alias = $join['alias'];
            if (!isset($this->aliases[$alias])) {
                $this->aliases[$alias] = $this->generateTableAlias();
            }
            $join['alias'] = $this->aliases[$alias];

            $joinId = $this->buildJoinIdentifier($join, $parentJoinId);
            if (isset($this->tableAliases[$joinId])) {
                $this->aliases[$alias] = $this->tableAliases[$joinId];
                $join['alias']         = $this->aliases[$alias];
            }

            $join['processed'] = true;
            $joins[] = $join;
        }
    }

    /**
     * @param array       $join
     * @param string      $parentJoinId
     * @param string|null $joinType
     *
     * @return string
     */
    protected function buildJoinIdentifier(array $join, $parentJoinId, $joinType = null)
    {
        return $this->joinIdHelper->buildJoinIdentifier(
            $join['join'],
            $parentJoinId,
            $joinType ?: $join['type'],
            $this->getJoinDefinitionConditionType($join),
            $this->getDefinitionJoinCondition($join)
        );
    }

    /**
     * @param array $join
     *
     * @return string|null
     */
    protected function getJoinDefinitionConditionType(array $join)
    {
        return isset($join['conditionType'])
            ? $join['conditionType']
            : null;
    }

    /**
     * @param array $join
     *
     * @return string|null
     */
    protected function getDefinitionJoinCondition(array $join)
    {
        return isset($join['condition'])
            ? $join['condition']
            : null;
    }

    /**
     * @param array  $query
     * @param string $mainEntityJoinId
     *
     * @return array
     */
    protected function registerVirtualColumnQueryAliases($query, $mainEntityJoinId)
    {
        $this->replaceTableAliasesInVirtualColumnQuery(
            $query,
            $this->getTableAliasesForVirtualColumnQuery($query, $mainEntityJoinId)
        );

        if (isset($query['join'])) {
            $joinMap = $this->getJoinMapForVirtualColumnQuery($query);
            $finished = false;
            while (!$finished) {
                $finished = true;
                $alias    = null;
                $newAlias = null;
                $joinId   = null;
                foreach ($joinMap as $alias => $mapItem) {
                    if (isset($mapItem['processed'])) {
                        continue;
                    }
                    $joinType     = $mapItem['type'];
                    $join         = $query['join'][$joinType][$mapItem['key']];
                    $parentJoinId = $this->getParentJoinIdForVirtualColumnJoin($join['join'], $mainEntityJoinId);
                    if (null !== $parentJoinId) {
                        $alias    = $join['alias'];
                        $joinId   = $this->buildJoinIdentifier($join, $parentJoinId, $joinType);
                        $newAlias = $this->findExistingTableAlias($joinId, $alias);
                        if ($newAlias) {
                            break;
                        }
                    }
                }
                if (!$newAlias) {
                    foreach ($joinMap as $alias => $mapItem) {
                        if (!isset($mapItem['processed'])) {
                            $joinType     = $mapItem['type'];
                            $join         = $query['join'][$joinType][$mapItem['key']];
                            $parentJoinId = $this->getParentJoinIdForVirtualColumnJoin(
                                $join['join'],
                                $mainEntityJoinId
                            );
                            if (null !== $parentJoinId) {
                                $alias    = $join['alias'];
                                $newAlias = $this->generateTableAlias();
                                $joinId   = str_replace(
                                    $alias,
                                    $newAlias,
                                    $this->buildJoinIdentifier($join, $parentJoinId, $joinType)
                                );
                                $this->registerTableAlias($joinId, $newAlias);
                                break;
                            }
                        }
                    }
                }
                if ($newAlias) {
                    $finished = false;
                    $this->replaceTableAliasesInVirtualColumnQuery($query, [$alias => $newAlias]);
                    $joinMap[$newAlias] = array_merge(
                        $joinMap[$alias],
                        ['processed' => true, 'joinId' => $joinId]
                    );
                    unset($joinMap[$alias]);
                }
            }
        }

        return $query;
    }

    /**
     * @param string $virtualColumnJoinId
     * @param string $virtualColumnJoinAlias
     *
     * @return string|null
     */
    protected function findExistingTableAlias($virtualColumnJoinId, $virtualColumnJoinAlias)
    {
        $foundAlias = null;
        foreach ($this->tableAliases as $existingJoinId => $existingAlias) {
            if ($existingJoinId
                && str_replace($virtualColumnJoinAlias, $existingAlias, $virtualColumnJoinId) === $existingJoinId
            ) {
                $foundAlias = $existingAlias;
                break;
            }
        }

        return $foundAlias;
    }

    /**
     * @param string $joinExpr
     * @param string $mainEntityJoinId
     *
     * @return string|null
     */
    protected function getParentJoinIdForVirtualColumnJoin($joinExpr, $mainEntityJoinId)
    {
        $parts = explode('.', $joinExpr, 2);

        $parentAlias = count($parts) === 2
            ? $parts[0]
            : null;

        $parentJoinId = null;
        if (null === $parentAlias) {
            $parentJoinId = self::ROOT_ALIAS_KEY;
        } elseif ($this->tableAliases[$mainEntityJoinId] === $parentAlias) {
            $parentJoinId = $mainEntityJoinId;
        } elseif (isset($joinMap[$parentAlias]['processed'])) {
            $parentJoinId = $joinMap[$parentAlias]['joinId'];
        }

        return $parentJoinId;
    }

    /**
     * @param array  $query
     * @param string $mainEntityJoinId
     *
     * @return array
     */
    protected function getTableAliasesForVirtualColumnQuery($query, $mainEntityJoinId)
    {
        $aliases = [
            $this->getQueryRootAlias($query) => $this->tableAliases[$mainEntityJoinId]
        ];
        if (isset($query['join'])) {
            $counter = 0;
            foreach ($query['join'] as $joins) {
                foreach ($joins as $join) {
                    $aliases[$join['alias']] = sprintf('__tmp%d__', ++$counter);
                }
            }
        }

        return $aliases;
    }

    /**
     * @param array $query
     *
     * @return array
     */
    protected function getJoinMapForVirtualColumnQuery($query)
    {
        $joinMap = [];
        foreach ($query['join'] as $joinType => $joins) {
            foreach ($joins as $key => $join) {
                $parentAlias = null;
                $parts = explode('.', $join['join'], 2);
                if (count($parts)) {
                    $parentAlias = $parts[0];
                }
                $joinMap[$join['alias']] = [
                    'type'        => $joinType,
                    'key'         => $key,
                    'parentAlias' => $parentAlias
                ];
            }
        }

        return $joinMap;
    }

    /**
     * @param array $query
     * @param array $aliases
     */
    protected function replaceTableAliasesInVirtualColumnQuery(&$query, $aliases)
    {
        if (isset($query['join'])) {
            foreach ($query['join'] as &$joins) {
                QueryUtils::replaceTableAliasesInJoinAlias($joins, $aliases);
                QueryUtils::replaceTableAliasesInJoinExpr($joins, $aliases);
                QueryUtils::replaceTableAliasesInJoinConditions($joins, $aliases);
            }
            unset($joins);
        }
        $query['select']['expr'] = QueryUtils::replaceTableAliasesInSelectExpr($query['select']['expr'], $aliases);
    }

    /**
     * Replaces all table aliases declared in the virtual column query with unique aliases for built query
     *
     * @param array $joins
     */
    protected function replaceTableAliasesInVirtualColumnJoinConditions(&$joins)
    {
        QueryUtils::replaceTableAliasesInJoinConditions($joins, $this->aliases);
    }

    /**
     * Replaces all table aliases declared in the virtual column select expression with unique aliases for built query
     *
     * @param string $selectExpr
     *
     * @return string The corrected select expression
     */
    protected function replaceTableAliasesInVirtualColumnSelect($selectExpr)
    {
        return QueryUtils::replaceTableAliasesInSelectExpr($selectExpr, $this->aliases);
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
        $relationType = $this->joinIdHelper->getJoinType($joinId);
        if ($relationType) {
            return $relationType;
        }

        return null;
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
     * @param string $entityClassName
     *
     * @return string
     */
    protected function getUnidirectionalJoinCondition($joinTableAlias, $joinFieldName, $joinAlias, $entityClassName)
    {
        return sprintf('%s.%s = %s', $joinAlias, $joinFieldName, $joinTableAlias);
    }

    /**
     * Generates new unique table alias.
     *
     * @return string
     */
    protected function generateTableAlias()
    {
        $this->tableAliasesCount++;
        return sprintf(static::TABLE_ALIAS_TEMPLATE, $this->tableAliasesCount);
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
        $alisKey = $this->replaceJoinsForVirtualRelation($parentJoinId);

        if (empty($this->tableAliases[$alisKey])) {
            return $this->tableAliases[self::ROOT_ALIAS_KEY];
        }

        return $this->tableAliases[$alisKey];
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

    /**
     * @param string $rootEntity
     */
    protected function setRootEntity($rootEntity)
    {
        $this->rootEntity = $rootEntity;
    }
}
