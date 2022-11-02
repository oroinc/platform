<?php

namespace Oro\Bundle\QueryDesignerBundle\QueryDesigner;

use Oro\Bundle\BatchBundle\ORM\QueryBuilder\QueryBuilderTools;
use Oro\Bundle\EntityBundle\Provider\VirtualFieldProviderInterface;
use Oro\Bundle\EntityBundle\Provider\VirtualRelationProviderInterface;
use Oro\Bundle\QueryDesignerBundle\Exception\InvalidConfigurationException;
use Oro\Bundle\QueryDesignerBundle\Model\AbstractQueryDesigner;

/**
 * Provides a base functionality to convert a query definition created by the query designer to another format.
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
abstract class AbstractQueryConverter
{
    protected const MAX_ITERATIONS = 100;

    protected const INNER_JOIN = 'inner';
    protected const LEFT_JOIN  = 'left';

    protected const JOIN_CONDITION_TYPE_WITH = 'WITH';

    /** @var FunctionProviderInterface */
    private $functionProvider;

    /** @var VirtualFieldProviderInterface */
    private $virtualFieldProvider;

    /** @var VirtualRelationProviderInterface */
    private $virtualRelationProvider;

    /** @var QueryBuilderTools */
    private $qbTools;

    /** @var QueryConverterContext */
    private $convertContext;

    /** @var JoinIdentifierHelper|null */
    private $joinIdHelper;

    public function __construct(
        FunctionProviderInterface $functionProvider,
        VirtualFieldProviderInterface $virtualFieldProvider,
        VirtualRelationProviderInterface $virtualRelationProvider
    ) {
        $this->functionProvider = $functionProvider;
        $this->virtualFieldProvider = $virtualFieldProvider;
        $this->virtualRelationProvider = $virtualRelationProvider;
        $this->qbTools = new QueryBuilderTools();
        $this->convertContext = $this->createContext();
    }

    /**
     * Stores all table aliases in the query
     *
     * @param array $tableAliases [join id => alias, ...]
     */
    abstract protected function saveTableAliases(array $tableAliases): void;

    /**
     * Stores all column aliases in the query.
     *
     * @param array $columnAliases [column id => alias, ...]
     */
    abstract protected function saveColumnAliases(array $columnAliases): void;

    /**
     * Performs conversion of a single column of SELECT statement
     *
     * @param string                        $entityClass
     * @param string                        $tableAlias
     * @param string                        $fieldName
     * @param string                        $columnExpr
     * @param string                        $columnAlias
     * @param string                        $columnLabel
     * @param string|FunctionInterface|null $functionExpr
     * @param string|null                   $functionReturnType
     * @param bool                          $isDistinct
     */
    abstract protected function addSelectColumn(
        string $entityClass,
        string $tableAlias,
        string $fieldName,
        string $columnExpr,
        string $columnAlias,
        string $columnLabel,
        $functionExpr,
        ?string $functionReturnType,
        bool $isDistinct
    ): void;

    /**
     * Performs conversion of a single table of FROM statement
     */
    abstract protected function addFromStatement(string $entityClass, string $tableAlias): void;

    /**
     * Performs conversion of a single JOIN statement
     */
    abstract protected function addJoinStatement(
        ?string $joinType,
        string $join,
        string $joinAlias,
        ?string $joinConditionType,
        ?string $joinCondition
    ): void;

    /**
     * Opens new group in WHERE statement
     */
    abstract protected function beginWhereGroup(): void;

    /**
     * Closes current group in WHERE statement
     */
    abstract protected function endWhereGroup(): void;

    /**
     * Adds an operator to WHERE condition
     *
     * @param string $operator An operator. Can be AND or OR
     */
    abstract protected function addWhereOperator(string $operator): void;

    /**
     * Performs conversion of a single WHERE condition
     *
     * @param string                        $entityClass
     * @param string                        $tableAlias
     * @param string                        $fieldName
     * @param string                        $columnExpr
     * @param string|null                   $columnAlias
     * @param string                        $filterName
     * @param array                         $filterData
     * @param string|FunctionInterface|null $functionExpr
     */
    abstract protected function addWhereCondition(
        string $entityClass,
        string $tableAlias,
        string $fieldName,
        string $columnExpr,
        ?string $columnAlias,
        string $filterName,
        array $filterData,
        $functionExpr = null
    ): void;

    /**
     * Performs conversion of a single column of GROUP BY statement
     */
    abstract protected function addGroupByColumn(string $columnAlias): void;

    /**
     * Performs conversion of a single column of ORDER BY statement
     */
    abstract protected function addOrderByColumn(string $columnAlias, string $columnSorting): void;

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
    public function ensureSiblingTableJoined(string $tableAlias, string $joinByFieldName): string
    {
        $joinId = $this->getJoinIdentifierByTableAlias($tableAlias);
        $parentJoinId = $this->getParentJoinIdentifier($joinId);
        $newJoinId = $this->buildSiblingJoinIdentifier($parentJoinId, $joinByFieldName);

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
    public function ensureChildTableJoined(
        string $tableAlias,
        string $joinByFieldName,
        string $joinType = null
    ): string {
        $parentJoinId = $this->getJoinIdentifierByTableAlias($tableAlias);
        $joinId = $this->getJoinIdHelper()->buildJoinIdentifier(
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
     *
     * @return string The table alias for the given join
     */
    public function ensureTableJoined(string $joinId): string
    {
        if (!$this->context()->hasTableAlias($joinId)) {
            $this->addTableAliasesForJoinIdentifier($joinId);
        }

        return $this->context()->getTableAlias($joinId);
    }

    /**
     * Gets join identifier for the given table alias
     */
    public function getJoinIdentifierByTableAlias(string $tableAlias): ?string
    {
        return $this->context()->findJoin($tableAlias);
    }

    /**
     * Builds join identifier for a table is joined on the same level as a table identified by $joinId.
     *
     * @param string $joinId          The join identifier
     * @param string $joinByFieldName The name of a field should be used to join new table
     *
     * @return string The join identifier
     */
    public function buildSiblingJoinIdentifier(string $joinId, string $joinByFieldName): string
    {
        return $this->getJoinIdHelper()->buildSiblingJoinIdentifier($joinId, $joinByFieldName);
    }

    /**
     * Extracts a parent join identifier
     *
     * @throws \LogicException if incorrect join identifier specified
     */
    public function getParentJoinIdentifier(string $joinId): string
    {
        return $this->getJoinIdHelper()->getParentJoinIdentifier($joinId);
    }

    /**
     * Converts a query from the query designer format to a target format.
     * This is the main entry point for a query conversion.
     *
     * @throws InvalidConfigurationException
     */
    protected function doConvert(AbstractQueryDesigner $source): void
    {
        $this->initContext($source);
        try {
            $this->doConvertQuery();
        } finally {
            $this->resetContext();
        }
    }

    /**
     * Creates a new instance of the query conversion context.
     */
    protected function createContext(): QueryConverterContext
    {
        return new QueryConverterContext();
    }

    /**
     * Gets the query conversion context.
     */
    protected function context(): QueryConverterContext
    {
        return $this->convertContext;
    }

    /**
     * Initializes the query conversion context.
     *
     * @throws InvalidConfigurationException
     */
    protected function initContext(AbstractQueryDesigner $source): void
    {
        $this->context()->init($source);
        $this->joinIdHelper = new JoinIdentifierHelper($this->context()->getRootEntity());
    }

    /**
     * Resets the query conversion context to its initial state.
     */
    protected function resetContext(): void
    {
        $this->context()->reset();
        $this->joinIdHelper = null;
    }

    final protected function getJoinIdHelper(): JoinIdentifierHelper
    {
        return $this->joinIdHelper;
    }

    /**
     * Returns the JSON representation of the given query definition.
     */
    final protected function encodeDefinition(array $definition): string
    {
        return QueryDefinitionUtil::encodeDefinition($definition);
    }

    /**
     * Decodes the JSON representation of the given query definition.
     *
     * @throw InvalidConfigurationException if the JSON representation is not valid
     */
    final protected function decodeDefinition(?string $encodedDefinition): array
    {
        return QueryDefinitionUtil::decodeDefinition($encodedDefinition);
    }

    /**
     * The factory method that provides an algorithm used to convert a query
     * from the query designer format to a target format.
     */
    protected function doConvertQuery(): void
    {
        $this->prepareTableAliases();
        $this->prepareColumnAliases();

        $this->addSelectStatement();
        $this->addFromStatements();
        $this->addJoinStatements();
        $this->addWhereStatement();
        $this->addGroupByStatement();
        $this->addOrderByStatement();

        $this->saveTableAliases($this->context()->getTableAliases());
        $this->saveColumnAliases($this->context()->getColumnAliases());
    }

    /**
     * Prepares aliases for tables involved to a query
     */
    protected function prepareTableAliases(): void
    {
        $this->addTableAliasForRootEntity();
        $definition = $this->context()->getDefinition();
        if (isset($definition['filters'])) {
            $this->addTableAliasesForFilters($definition['filters']);
        }
        foreach ($definition['columns'] as $column) {
            $this->addTableAliasesForColumn($column['name']);
        }
        if (isset($definition['grouping_columns'])) {
            foreach ($definition['grouping_columns'] as $column) {
                $this->addTableAliasesForColumn($column['name']);
            }
        }
    }

    /**
     * Prepares aliases for columns should be returned by a query
     */
    protected function prepareColumnAliases(): void
    {
        $context = $this->context();
        $definition = $context->getDefinition();
        foreach ($definition['columns'] as $column) {
            $columnId = $this->buildColumnIdentifier($column);
            if (!$context->hasColumnAlias($columnId)) {
                $context->setColumnAlias($columnId, $context->generateColumnAlias(), $column['name']);
            }
        }
    }

    /**
     * @param array $column
     *
     * @return array [string|FunctionInterface|null, string|null]
     */
    private function getColumnFunction(array $column): array
    {
        if (empty($column['func'])) {
            return [null, null];
        }

        $func = $column['func'];
        $function = $this->functionProvider->getFunction($func['name'], $func['group_name'], $func['group_type']);

        return [$function['expr'], $function['return_type'] ?? null];
    }

    /**
     * Performs conversion of SELECT statement
     */
    protected function addSelectStatement(): void
    {
        $context = $this->context();
        $definition = $context->getDefinition();
        foreach ($definition['columns'] as $column) {
            $columnName = $column['name'];
            $fieldName = $this->getFieldName($columnName);
            [$functionExpr, $functionReturnType] = $this->getColumnFunction($column);
            $isDistinct = !empty($column['distinct']);
            $tableAlias = $this->getTableAliasForColumn($columnName);
            $columnLabel = $column['label'] ?? $fieldName;
            $this->addSelectColumn(
                $this->getEntityClass($columnName),
                $tableAlias,
                $fieldName,
                $this->buildColumnExpression($columnName, $tableAlias, $fieldName),
                $context->getColumnAlias($this->buildColumnIdentifier($column)),
                $columnLabel,
                $functionExpr,
                $functionReturnType,
                $isDistinct
            );
        }
    }

    /**
     * Performs conversion of FROM statement
     */
    protected function addFromStatements(): void
    {
        $this->addFromStatement($this->context()->getRootEntity(), $this->context()->getRootTableAlias());
    }

    /**
     * Performs conversion of JOIN statements
     */
    protected function addJoinStatements(): void
    {
        $joinIdHelper = $this->getJoinIdHelper();
        $context = $this->context();
        $tableAliases = $context->getTableAliases();
        foreach ($tableAliases as $joinId => $joinAlias) {
            if (!empty($joinId)) {
                $parentJoinId = $this->getParentJoinIdentifier($joinId);
                $joinTableAlias = $tableAliases[$parentJoinId];

                $virtualRelation = $context->findJoinByVirtualRelationJoin($parentJoinId);
                if (null !== $virtualRelation) {
                    $joinTableAlias = $context->getAlias($this->virtualRelationProvider->getTargetJoinAlias(
                        $this->getEntityClass($virtualRelation),
                        $this->getFieldName($virtualRelation),
                        $this->getFieldName($joinId)
                    ));
                }

                if ($joinIdHelper->isUnidirectionalJoin($joinId)) {
                    $entityClass = $this->getEntityClass($joinId);
                    $joinFieldName = $this->getFieldName($joinId);
                    $this->addJoinStatement(
                        $this->getJoinType($joinId),
                        $entityClass,
                        $joinAlias,
                        self::JOIN_CONDITION_TYPE_WITH,
                        $this->getUnidirectionalJoinCondition(
                            $joinTableAlias,
                            $joinFieldName,
                            $joinAlias,
                            $entityClass
                        )
                    );
                } elseif ($joinIdHelper->isUnidirectionalJoinWithCondition($joinId)) {
                    // such as "Entity:Name|left|WITH|t2.field = t1"

                    $entityClass = $joinIdHelper->getUnidirectionalJoinEntityName($joinId);
                    $this->addJoinStatement(
                        $this->getJoinType($joinId),
                        $entityClass,
                        $joinAlias,
                        $joinIdHelper->getJoinConditionType($joinId),
                        $joinIdHelper->getJoinCondition($joinId)
                    );
                } else {
                    // bidirectional
                    $join = null === $this->getEntityClass($joinId)
                        ? $joinIdHelper->getJoin($joinId)
                        : sprintf('%s.%s', $joinTableAlias, $this->getFieldName($joinId));
                    $this->addJoinStatement(
                        $this->getJoinType($joinId),
                        $join,
                        $joinAlias,
                        $joinIdHelper->getJoinConditionType($joinId),
                        $joinIdHelper->getJoinCondition($joinId)
                    );
                }
            }
        }
    }

    /**
     * Returns a string which can be used in a query to get column value
     */
    protected function buildColumnExpression(string $columnName, string $tableAlias, string $fieldName): string
    {
        return $this->context()->hasVirtualColumnExpression($columnName)
            ? $this->context()->getVirtualColumnExpression($columnName)
            : sprintf('%s.%s', $tableAlias, $fieldName);
    }

    /**
     * Performs conversion of WHERE statement
     */
    protected function addWhereStatement(): void
    {
        $definition = $this->context()->getDefinition();
        if (!empty($definition['filters'])) {
            $this->addWhereFilters($definition['filters'], new FiltersParserContext());
        }
    }

    private function addWhereFilters(array $filters, FiltersParserContext $context): void
    {
        $context->checkBeginGroup();
        $this->beginWhereGroup();

        $context->setLastTokenType(FiltersParserContext::BEGIN_GROUP_TOKEN);
        foreach ($filters as $token) {
            if (\is_string($token)) {
                $context->checkOperator($token);
                $this->addWhereOperator(strtoupper($token));
                $context->setLastTokenType(FiltersParserContext::OPERATOR_TOKEN);
            } elseif (\is_array($token) && isset($token['criterion'])) {
                $context->checkFilter($token);
                $this->addWhereFilter($token);
                $context->setLastTokenType(FiltersParserContext::FILTER_TOKEN);
            } else {
                if (empty($token)) {
                    $context->throwInvalidFiltersException('a group must not be empty');
                }
                $this->addWhereFilters($token, $context);
            }
            $context->setLastToken($token);
        }

        $context->checkEndGroup();
        $this->endWhereGroup();

        $context->setLastTokenType(FiltersParserContext::END_GROUP_TOKEN);
    }

    private function addWhereFilter(array $filter): void
    {
        $columnName = $filter['columnName'] ?? '';
        $fieldName = $this->getFieldName($columnName);
        $tableAlias = $this->getTableAliasForColumn($columnName);
        [$functionExpr] = $this->getColumnFunction($this->buildWhereFilterColumn($fieldName, $filter));

        $this->addWhereCondition(
            $this->getEntityClass($columnName),
            $tableAlias,
            $fieldName,
            $this->buildColumnExpression($columnName, $tableAlias, $fieldName),
            $this->context()->findColumnAlias(
                $this->buildColumnIdentifier($this->buildWhereFilterColumn($columnName, $filter))
            ),
            $filter['criterion']['filter'],
            $filter['criterion']['data'],
            $functionExpr
        );
    }

    /**
     * @param string $columnName
     * @param array  $filter
     *
     * @return string[]
     */
    private function buildWhereFilterColumn(string $columnName, array $filter): array
    {
        $column = ['name' => $columnName];
        if (!empty($filter['func'])) {
            $column['func'] = $filter['func'];
        }

        return $column;
    }
    /**
     * Performs conversion of GROUP BY statement
     */
    protected function addGroupByStatement(): void
    {
        $context = $this->context();
        $definition = $context->getDefinition();
        if (isset($definition['grouping_columns'])) {
            foreach ($definition['grouping_columns'] as $column) {
                $columnId = $this->buildColumnIdentifier($column);
                if (!$context->hasColumnAlias($columnId)) {
                    throw new InvalidConfigurationException(sprintf(
                        'The grouping column "%s" must be declared in SELECT clause.',
                        $column['name']
                    ));
                }
                $this->addGroupByColumn($context->getColumnAlias($columnId));
            }
        }
    }

    /**
     * Performs conversion of ORDER BY statement
     */
    protected function addOrderByStatement(): void
    {
        $context = $this->context();
        $definition = $context->getDefinition();
        foreach ($definition['columns'] as $column) {
            if (!empty($column['sorting'])) {
                $this->addOrderByColumn(
                    $context->getColumnAlias($this->buildColumnIdentifier($column)),
                    $column['sorting']
                );
            }
        }
    }

    /**
     * Generates and saves an alias for the root entity
     */
    protected function addTableAliasForRootEntity(): void
    {
        $this->registerTableAlias($this->context()->getRootJoinId());
    }

    /**
     * Generates and saves aliases for the given join identifier and all its parents
     */
    private function addTableAliasesForJoinIdentifier(string $joinId): void
    {
        $this->addTableAliasesForJoinIdentifiers(
            $this->getJoinIdHelper()->explodeJoinIdentifier($joinId)
        );
    }

    /**
     * Generates and saves aliases for the given column and all its parent joins
     *
     * @param string $columnName String with specified format
     *                           rootEntityField+Class\Name::joinedEntityRelation+Relation\Class::fieldToSelect
     */
    protected function addTableAliasesForColumn(string $columnName): void
    {
        $this->addTableAliasesForVirtualRelation($columnName);
        $this->addTableAliasesForVirtualField($columnName);
    }

    /**
     * Generates and saves table aliases for the given filters
     */
    protected function addTableAliasesForFilters(array $filters): void
    {
        foreach ($filters as $filter) {
            if (\is_array($filter)) {
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
     */
    private function addTableAliasesForVirtualField(string $columnName): void
    {
        if ($this->context()->hasVirtualColumnExpression($columnName)) {
            // already processed
            return;
        }

        $entityClass = $this->getEntityClass($columnName);
        $fieldName = $this->getFieldName($columnName);
        if (!$entityClass || !$this->isVirtualField($entityClass, $fieldName)) {
            // not a virtual field
            return;
        }

        $mainEntityJoinId = $this->getParentJoinIdentifier($this->buildColumnJoinIdentifier($columnName));
        $this->addVirtualColumn($columnName, $entityClass, $fieldName, $mainEntityJoinId);
    }

    /**
     * Checks if the given column is a virtual field and if so, generates and saves table aliases for it
     */
    private function addTableAliasesForVirtualFieldWithParentJoinId(
        string $columnName,
        string $mainEntityJoinId
    ): void {
        if ($this->context()->hasVirtualColumnExpression($columnName)) {
            // already processed
            return;
        }

        $entityClass = $this->getEntityClass($columnName);
        $fieldName = $this->getFieldName($columnName);
        if (!$entityClass || !$this->isVirtualField($entityClass, $fieldName)) {
            // not a virtual field
            return;
        }

        $this->addVirtualColumn($columnName, $entityClass, $fieldName, $mainEntityJoinId);
    }

    private function addVirtualColumn(
        string $columnName,
        string $entityClass,
        string $fieldName,
        string $mainEntityJoinId
    ): void {
        $query = $this->registerVirtualColumnQueryAliases(
            $this->virtualFieldProvider->getVirtualFieldQuery($entityClass, $fieldName),
            $mainEntityJoinId
        );

        $this->context()->setVirtualColumnExpression($columnName, $query['select']['expr']);
        $columnJoinId = $this->buildColumnJoinIdentifier($fieldName, $entityClass);
        if (!$this->context()->hasVirtualColumnOptions($columnJoinId)) {
            $options = $query['select'];
            unset($options['expr']);
            $this->context()->setVirtualColumnOptions($columnJoinId, $options);
        }
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    private function replaceJoinsForVirtualRelation(string $joinId): string
    {
        $context = $this->context();

        /**
         * mainEntityJoinId - parent join definition
         *
         * For `Root\Class::rootEntityField+Class\Name::joinedEntityRelation` parent is `Root\Class::rootEntityField`
         */
        $mainEntityJoinId = $context->getRootJoinId();

        /**
         * columnJoinIds - array of joins path
         *
         * For `Root\Class::rootEntityField+Class\Name::joinedEntityRelation` will be
         *
         * - `Root\Class::rootEntityField`
         * - `Class\Name::joinedEntityRelation`
         */
        $columnJoinIds = $this->getJoinIdHelper()->splitJoinIdentifier($joinId);

        $tableAliases = $context->getTableAliases();

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
            if ($context->hasVirtualRelationJoin($columnJoinId)) {
                $columnJoinId = trim(
                    str_replace($mainEntityJoinId, '', $context->getVirtualRelationJoin($columnJoinId)),
                    '+'
                );
                $relationColumnJoinIds = $this->getJoinIdHelper()->splitJoinIdentifier($columnJoinId);
                $fullRelationColumnJoinId = $context->getRootJoinId();
                foreach ($relationColumnJoinIds as $relationColumnJoinId) {
                    $mainEntityJoinId = trim($mainEntityJoinId . '+' . $relationColumnJoinId, '+');
                    $fullRelationColumnJoinId = trim($fullRelationColumnJoinId . '+' . $relationColumnJoinId, '+');
                    $tableAlias = null;
                    if (!empty($tableAliases[$fullRelationColumnJoinId])) {
                        $tableAlias = $tableAliases[$fullRelationColumnJoinId];
                    }

                    $this->registerTableAlias($mainEntityJoinId, $tableAlias);
                }

                continue;
            }

            $entityClass = $this->getEntityClass($columnJoinId);
            $fieldName = $this->getFieldName($columnJoinId);

            if (!$this->isVirtualRelation($entityClass, $fieldName)) {
                /**
                 * Was joined previously in virtual relation
                 */
                if ($context->hasAlias($fieldName)) {
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

            $query = $this->virtualRelationProvider->getVirtualRelationQuery($entityClass, $fieldName);
            $mainEntityJoinAlias = $tableAliases[$mainEntityJoinId];

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
            $this->replaceTableAliasesInVirtualColumnJoinConditions($joins, $context->getAliases());

            /**
             * Store mainEntityJoinId to build columnJoinId after virtual relations joins build
             *
             * `Root\Class::rootEntityField`
             */
            $baseMainEntityJoinId = $mainEntityJoinId;
            $virtualJoinId = $context->getRootJoinId();
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
            $context->setVirtualRelationJoin($columnJoinId, $virtualJoinId);

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
        return $this->getJoinIdHelper()->mergeJoinIdentifier(array_filter($columnJoinIds));
    }

    /**
     * Replaces all table aliases declared in the virtual column query with unique aliases for built query.
     */
    private function replaceTableAliasesInVirtualColumnJoinConditions(array &$joins, array $aliases): void
    {
        QueryExprUtil::replaceTableAliasesInJoinConditions($joins, $aliases);
    }

    private function prepareAliases(array $query, string $mainEntityJoinAlias): void
    {
        $this->context()->setAlias($this->getQueryRootAlias($query), $mainEntityJoinAlias);
    }

    private function getQueryRootAlias(array $query): string
    {
        return $query['root_alias'] ?? 'entity';
    }

    private function buildVirtualJoins(array $query, string $mainEntityJoinId): array
    {
        $joins = [];
        $iterations = 0;

        $this->buildQueryAliases($query);

        $context = $this->context();
        do {
            $this->processVirtualColumnJoins($joins, $query, self::INNER_JOIN, $mainEntityJoinId);
            $this->processVirtualColumnJoins($joins, $query, self::LEFT_JOIN, $mainEntityJoinId);

            if ($iterations > self::MAX_ITERATIONS) {
                throw new \RuntimeException(
                    'Could not reorder joins correctly. Number of tries has exceeded maximum allowed.'
                );
            }

            $iterations++;
        } while (count($context->getAliases()) !== count($context->getQueryAliases()));
        $context->setQueryAliases([]);

        return $joins;
    }

    private function registerTableAlias(string $joinId, string $tableAlias = null): void
    {
        if (!$this->context()->hasTableAlias($joinId)) {
            if (!$tableAlias) {
                $tableAlias = $this->context()->generateTableAlias();
            }

            $this->context()->setTableAlias($joinId, $tableAlias);
        }
    }

    /**
     * Checks if the given column is a virtual relation and if so, generates and saves table aliases for it
     */
    private function addTableAliasesForVirtualRelation(string $columnName): void
    {
        if ($this->context()->hasVirtualColumnExpression($columnName)) {
            // already processed
            return;
        }

        $joinIds = $this->addTableAliasesForJoinIdentifiers(
            $this->getJoinIdHelper()->explodeColumnName($columnName)
        );

        if (!$this->context()->hasVirtualRelationJoins()) {
            return;
        }

        $hasVirtualRelation = false;
        foreach ($joinIds as $columnJoinId) {
            if ($this->context()->findJoinByVirtualRelationJoin($columnJoinId)) {
                $hasVirtualRelation = true;
                break;
            }
        }
        if (!$hasVirtualRelation) {
            return;
        }

        // check if last field is virtual, if yes, add tableAlias to related virtual field
        $isLastVirtualField = $this->isVirtualField(
            $this->getEntityClass($columnName),
            $this->getFieldName($columnName)
        );
        if ($isLastVirtualField) {
            $this->addTableAliasesForVirtualFieldWithParentJoinId($columnName, $columnJoinId);
            return;
        }

        $parentJoinId = $this->getParentJoinIdentifier($this->buildColumnJoinIdentifier($columnName));
        $fieldName = $this->getFieldName($parentJoinId);
        $entityClass = $this->getEntityClass($parentJoinId);
        if ($this->isVirtualRelation($entityClass, $fieldName)) {
            $tableAlias = $this->context()->getAlias($this->virtualRelationProvider->getTargetJoinAlias(
                $entityClass,
                $fieldName,
                $this->getFieldName($columnName)
            ));
        } else {
            $joinId = end($joinIds);
            $tableAlias = $this->context()->getTableAlias($joinId);
        }

        $this->context()->setVirtualColumnExpression(
            $columnName,
            sprintf('%s.%s', $tableAlias, $this->getFieldName($columnName))
        );
    }

    /**
     * Generates and saves aliases for the given joins
     *
     * @param string[] $joinIds
     *
     * @return string[] updated ids of joins
     */
    private function addTableAliasesForJoinIdentifiers(array $joinIds): array
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
     */
    private function registerVirtualColumnTableAlias(array $joins, array $join, string $mainEntityJoinId): void
    {
        $tableAlias = $join['alias'];
        if ($this->context()->hasJoin($tableAlias)) {
            return;
        }

        $joinId = $this->buildVirtualColumnJoinIdentifier($joins, $join, $mainEntityJoinId);

        $this->registerTableAlias($joinId, $tableAlias);
    }

    private function buildVirtualColumnJoinIdentifier(array $joins, array $join, string $mainEntityJoinId): string
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

            if ($parentItems) {
                $parentItem = reset($parentItems);
                $parentAlias = $parentItem['alias'];
                if ($parentItem && !$this->context()->hasJoin($parentAlias)) {
                    $this->registerVirtualColumnTableAlias($joins, $parentItem, $mainEntityJoinId);
                }
            }

            if ($this->context()->hasJoin($parentJoinAlias)) {
                $parentJoinId = $this->context()->getJoin($parentJoinAlias);
            }
        }

        return $this->buildJoinIdentifier($join, $parentJoinId);
    }

    private function buildQueryAliases(array $query): void
    {
        $queryAliases = array_keys($this->context()->getAliases());
        foreach ([self::INNER_JOIN, self::LEFT_JOIN] as $type) {
            if (empty($query['join'][$type])) {
                continue;
            }

            foreach ($query['join'][$type] as $join) {
                $queryAliases[] = $join['alias'];
            }
        }
        $this->context()->setQueryAliases(array_unique($queryAliases));
    }

    /**
     * Processes all virtual column join declarations of $joinType type
     */
    private function processVirtualColumnJoins(
        array &$joins,
        array &$query,
        string $joinType,
        string $parentJoinId
    ): void {
        if (!isset($query['join'][$joinType])) {
            return;
        }

        $context = $this->context();
        $tableAliases = $context->getTableAliases();
        foreach ($query['join'][$joinType] as &$join) {
            if (!empty($join['processed'])) {
                continue;
            }

            $usedAliases = $this->qbTools->getTablesUsedInJoinCondition(
                $this->getDefinitionJoinCondition($join),
                $context->getQueryAliases()
            );
            $unknownAliases = array_diff(
                $usedAliases,
                array_merge(array_keys($context->getAliases()), [$join['alias']])
            );
            if ($unknownAliases) {
                continue;
            }

            $join['type'] = $joinType;
            $delimiterPos = strpos($join['join'], '.');
            if (false !== $delimiterPos) {
                $alias = substr($join['join'], 0, $delimiterPos);
                if (!$context->hasAlias($alias)) {
                    $context->setAlias($alias, $context->generateTableAlias());
                }
                $join['join'] = $context->getAlias($alias) . substr($join['join'], $delimiterPos);
            }

            $alias = $join['alias'];
            if (!$context->hasAlias($alias)) {
                $context->setAlias($alias, $context->generateTableAlias());
            }
            $join['alias'] = $context->getAlias($alias);

            $joinId = $this->buildJoinIdentifier($join, $parentJoinId);
            if (isset($tableAliases[$joinId])) {
                $context->setAlias($alias, $tableAliases[$joinId]);
                $join['alias'] = $context->getAlias($alias);
            }

            $join['processed'] = true;
            $joins[] = $join;
        }
    }

    protected function buildJoinIdentifier(array $join, string $parentJoinId, string $joinType = null): string
    {
        return $this->getJoinIdHelper()->buildJoinIdentifier(
            $join['join'],
            $parentJoinId,
            $joinType ?: $join['type'],
            $this->getJoinDefinitionConditionType($join),
            $this->getDefinitionJoinCondition($join)
        );
    }

    final protected function buildColumnJoinIdentifier(string $columnName, string $entityClass = null): string
    {
        return $this->getJoinIdHelper()->buildColumnJoinIdentifier($columnName, $entityClass);
    }

    /**
     * Returns a string that unique identify the given column.
     */
    final protected function buildColumnIdentifier(array $column): string
    {
        return QueryDefinitionUtil::buildColumnIdentifier($column);
    }

    private function getJoinDefinitionConditionType(array $join): ?string
    {
        return $join['conditionType'] ?? null;
    }

    private function getDefinitionJoinCondition(array $join): ?string
    {
        return $join['condition'] ?? null;
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function registerVirtualColumnQueryAliases(array $query, string $mainEntityJoinId): array
    {
        $this->replaceTableAliasesInVirtualColumnQuery(
            $query,
            $this->getTableAliasesForVirtualColumnQuery($query, $mainEntityJoinId)
        );

        if (isset($query['join'])) {
            $context = $this->context();
            $joinMap = $this->getJoinMapForVirtualColumnQuery($query);
            $finished = false;
            while (!$finished) {
                $finished = true;
                $alias = null;
                $newAlias = null;
                $joinId = null;
                foreach ($joinMap as $alias => $mapItem) {
                    if (isset($mapItem['processed'])) {
                        continue;
                    }
                    $joinType = $mapItem['type'];
                    $join = $query['join'][$joinType][$mapItem['key']];
                    $parentJoinId = $this->getParentJoinIdForVirtualColumnJoin(
                        $joinMap,
                        $join['join'],
                        $mainEntityJoinId
                    );
                    if (null !== $parentJoinId) {
                        $alias = $join['alias'];
                        $joinId = $this->buildJoinIdentifier($join, $parentJoinId, $joinType);
                        $newAlias = $this->findExistingTableAlias($joinId, $alias);
                        if ($newAlias) {
                            break;
                        }
                    }
                }
                if (!$newAlias) {
                    foreach ($joinMap as $alias => $mapItem) {
                        if (!isset($mapItem['processed'])) {
                            $joinType = $mapItem['type'];
                            $join = $query['join'][$joinType][$mapItem['key']];
                            $parentJoinId = $this->getParentJoinIdForVirtualColumnJoin(
                                $joinMap,
                                $join['join'],
                                $mainEntityJoinId
                            );
                            if (null !== $parentJoinId) {
                                $alias = $join['alias'];
                                $newAlias = $context->generateTableAlias();
                                $joinId = str_replace(
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
                    $joinMap[$newAlias] = array_merge($joinMap[$alias], ['processed' => true, 'joinId' => $joinId]);
                    unset($joinMap[$alias]);

                    /**
                     * It is required to update parentAliases in the map with the new alias,
                     * because there could be a case when one of the joins use another one and in this case
                     * second join alias will start to be invalid after replacement.
                     *
                     * Example of such virtual field con figuration:
                     * select:
                     *     expr:         defaultContactEmails.email
                     *     return_type:  string
                     * join:
                     *     left:
                     *         - { join: entity.defaultContact, alias: defaultContact }
                     *         - {
                     *             join: defaultContact.emails,
                     *             alias: defaultContactEmails,
                     *             conditionType: 'WITH',
                     *             condition: 'defaultContactEmails.primary = true'
                     *           }
                     * Example of $joinMap value for this configuration of the virtual field above:
                     *      [
                     *          '__tmp1__' => ['['type'=> left, 'key' => 0, 'parentAlias' => t1],
                     *          '__tmp2__' => ['['type'=> left, 'key' => 1, 'parentAlias' => __tmp1__],
                     *      ]
                     */
                    $joinMap = $this->replaceParentAliasInJoinMap($joinMap, $alias, $newAlias);
                }
            }
        }

        return $query;
    }

    private function replaceTableAliasesInVirtualColumnQuery(array &$query, array $aliases): void
    {
        if (isset($query['join'])) {
            foreach ($query['join'] as &$joins) {
                QueryExprUtil::replaceTableAliasesInJoinAlias($joins, $aliases);
                QueryExprUtil::replaceTableAliasesInJoinExpr($joins, $aliases);
                QueryExprUtil::replaceTableAliasesInJoinConditions($joins, $aliases);
            }
            unset($joins);
        }
        $query['select']['expr'] = QueryExprUtil::replaceTableAliasesInSelectExpr(
            $query['select']['expr'],
            $aliases
        );
    }

    private function replaceParentAliasInJoinMap(array $joinMap, string $from, string $to): array
    {
        foreach ($joinMap as $alias => $mapItem) {
            if (isset($mapItem['parentAlias']) && $mapItem['parentAlias'] === $from) {
                $joinMap[$alias]['parentAlias'] = $to;
            }
        }

        return $joinMap;
    }

    private function findExistingTableAlias(string $virtualColumnJoinId, string $virtualColumnJoinAlias): ?string
    {
        $foundAlias = null;
        $tableAliases = $this->context()->getTableAliases();
        foreach ($tableAliases as $existingJoinId => $existingAlias) {
            if ($existingJoinId
                && str_replace($virtualColumnJoinAlias, $existingAlias, $virtualColumnJoinId) === $existingJoinId
            ) {
                $foundAlias = $existingAlias;
                break;
            }
        }

        return $foundAlias;
    }

    private function getParentJoinIdForVirtualColumnJoin(
        array $joinMap,
        string $joinExpr,
        string $mainEntityJoinId
    ): ?string {
        $parts = explode('.', $joinExpr, 2);

        $parentAlias = count($parts) === 2
            ? $parts[0]
            : null;

        $parentJoinId = null;
        if (null === $parentAlias) {
            $parentJoinId = $this->context()->getRootJoinId();
        } elseif ($this->context()->getTableAlias($mainEntityJoinId) === $parentAlias) {
            $parentJoinId = $mainEntityJoinId;
        } elseif (isset($joinMap[$parentAlias]['processed'])) {
            $parentJoinId = $joinMap[$parentAlias]['joinId'];
        }

        return $parentJoinId;
    }

    private function getTableAliasesForVirtualColumnQuery(array $query, string $mainEntityJoinId): array
    {
        $aliases = [
            $this->getQueryRootAlias($query) => $this->context()->getTableAlias($mainEntityJoinId)
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

    private function getJoinMapForVirtualColumnQuery(array $query): array
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
     * Extracts an entity class name for the given column or from the given join identifier
     */
    protected function getEntityClass(string $columnNameOrJoinId): ?string
    {
        return $this->getJoinIdHelper()->getEntityClassName($columnNameOrJoinId);
    }

    /**
     * Extracts a field name for the given column or from the given join identifier
     */
    protected function getFieldName(string $columnNameOrJoinId): string
    {
        return $this->getJoinIdHelper()->getFieldName($columnNameOrJoinId);
    }

    /**
     * Gets a field data type
     */
    protected function getFieldType(string $entityClass, string $fieldName): ?string
    {
        $result = null;
        if ($this->isVirtualField($entityClass, $fieldName)) {
            // try to guess virtual column type
            $columnJoinId = $this->buildColumnJoinIdentifier($fieldName, $entityClass);
            if ($this->context()->hasVirtualColumnOption($columnJoinId, 'return_type')) {
                $result = $this->context()->getVirtualColumnOption($columnJoinId, 'return_type');
            }
        }

        return $result;
    }

    protected function isVirtualField(string $entityClass, string $fieldName): bool
    {
        if (!$fieldName) {
            return false;
        }

        return $this->virtualFieldProvider->isVirtualField($entityClass, $fieldName);
    }

    protected function isVirtualRelation(string $entityClass, string $fieldName): bool
    {
        if (!$fieldName) {
            return false;
        }

        return $this->virtualRelationProvider->isVirtualRelation($entityClass, $fieldName);
    }

    /**
     * Gets join type for the given join identifier
     *
     * @param string $joinId
     *
     * @return string|null NULL for autodetect, or a string represents the join type, for example 'inner' or 'left'
     */
    protected function getJoinType(string $joinId): ?string
    {
        $relationType = $this->getJoinIdHelper()->getJoinType($joinId);
        if ($relationType) {
            return $relationType;
        }

        return null;
    }

    /**
     * Gets the join condition the given join identifier
     */
    protected function getUnidirectionalJoinCondition(
        string $joinTableAlias,
        string $joinFieldName,
        string $joinAlias,
        string $entityClass
    ): string {
        return sprintf('%s.%s = %s', $joinAlias, $joinFieldName, $joinTableAlias);
    }

    /**
     * Returns a table alias for the given column
     */
    protected function getTableAliasForColumn(string $columnName): string
    {
        $parentJoinId = $this->getParentJoinIdentifier($this->buildColumnJoinIdentifier($columnName));
        $virtualJoinId = $this->replaceJoinsForVirtualRelation($parentJoinId);
        if ($this->context()->hasTableAlias($virtualJoinId)) {
            return $this->context()->getTableAlias($virtualJoinId);
        }

        return $this->context()->getTableAlias($this->context()->getRootJoinId());
    }

    /**
     * Prepares the given function expression to use in a query
     *
     * @param string|FunctionInterface $functionExpr
     * @param string                   $tableAlias
     * @param string                   $fieldName
     * @param string                   $columnName
     * @param string|null              $columnAlias
     *
     * @return string
     *
     * @throws InvalidConfigurationException if incorrect type $functionExpr specified
     */
    protected function prepareFunctionExpression(
        $functionExpr,
        string $tableAlias,
        string $fieldName,
        string $columnName,
        ?string $columnAlias
    ): string {
        if (\is_string($functionExpr) && strncmp($functionExpr, '@', 1) === 0) {
            $functionExprClass = substr($functionExpr, 1);
            $functionExpr = new $functionExprClass();
        }
        if ($functionExpr instanceof FunctionInterface) {
            return $functionExpr->getExpression($tableAlias, $fieldName, $columnName, $columnAlias, $this);
        }
        if (!\is_string($functionExpr)) {
            throw new InvalidConfigurationException(sprintf(
                'The function expression must be a string or instance of %s.',
                FunctionInterface::class
            ));
        }

        $variables = [
            'column'       => $columnName,
            'column_name'  => $fieldName,
            'column_alias' => $columnAlias,
            'table_alias'  => $tableAlias
        ];

        return preg_replace_callback(
            '/\$([\w\_]+)/',
            function ($matches) use (&$variables) {
                return $variables[$matches[1]];
            },
            $functionExpr
        );
    }
}
