<?php

namespace Oro\Bundle\QueryDesignerBundle\QueryDesigner;

use Oro\Bundle\QueryDesignerBundle\Model\AbstractQueryDesigner;
use Oro\Bundle\QueryDesignerBundle\Exception\InvalidConfigurationException;
use Oro\Bundle\QueryDesignerBundle\Exception\InvalidFilterLogicException;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
abstract class AbstractQueryConverter
{
    const COLUMN_ALIAS_TEMPLATE = 'c%d';
    const TABLE_ALIAS_TEMPLATE  = 't%d';

    /**
     * @var FunctionProviderInterface
     */
    protected $functionProvider;

    /**
     * @var string
     */
    protected $entity;

    /**
     * @var array
     */
    protected $definition;

    /**
     * @var array
     */
    protected $tableAliases;

    /**
     * @var array
     */
    protected $columnAliases;

    /**
     * Constructor
     *
     * @param FunctionProviderInterface $functionProvider
     */
    protected function __construct(FunctionProviderInterface $functionProvider)
    {
        $this->functionProvider = $functionProvider;
    }

    /**
     * Converts a query from the query designer format to a target format
     *
     * @param AbstractQueryDesigner $source
     * @throws InvalidConfigurationException
     */
    protected function doConvert(AbstractQueryDesigner $source)
    {
        $this->entity     = $source->getEntity();
        $this->definition = json_decode($source->getDefinition(), true);

        if (!isset($this->definition['columns'])) {
            throw new InvalidConfigurationException('The "columns" definition does not exist.');
        }
        if (empty($this->definition['columns'])) {
            throw new InvalidConfigurationException('The "columns" definition must not be empty.');
        }

        $this->tableAliases  = [];
        $this->columnAliases = [];
        $this->buildQuery();
        $this->tableAliases  = null;
        $this->columnAliases = null;
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
    }

    /**
     * Prepares aliases for tables involved to a query
     */
    protected function prepareTableAliases()
    {
        $this->addTableAliasesForJoinIdentifiers(['']);
        if (isset($this->definition['filters'])) {
            foreach ($this->definition['filters'] as $filter) {
                $this->addTableAliasesForJoinIdentifiers($this->getJoinIdentifiers($filter['columnName']));
            }
        }
        foreach ($this->definition['columns'] as $column) {
            $this->addTableAliasesForJoinIdentifiers($this->getJoinIdentifiers($column['name']));
        }
        if (isset($this->definition['grouping_columns'])) {
            foreach ($this->definition['grouping_columns'] as $column) {
                $this->addTableAliasesForJoinIdentifiers($this->getJoinIdentifiers($column['name']));
            }
        }
    }

    /**
     * Prepares aliases for columns should be returned by a query
     */
    protected function prepareColumnAliases()
    {
        foreach ($this->definition['columns'] as $column) {
            $this->columnAliases[$this->buildColumnAliasKey($column)] =
                sprintf(static::COLUMN_ALIAS_TEMPLATE, count($this->columnAliases) + 1);
        }
    }

    /**
     * Performs conversion of SELECT statement
     */
    protected function addSelectStatement()
    {
        foreach ($this->definition['columns'] as $column) {
            $fieldName          = $this->getFieldName($column['name']);
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
            $this->addSelectColumn(
                $this->getEntityClassName($column['name']),
                $this->getTableAliasForColumn($column['name']),
                $fieldName,
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
     * @param string      $entityClassName
     * @param string      $tableAlias
     * @param string      $fieldName
     * @param string      $columnAlias
     * @param string      $columnLabel
     * @param string|null $functionExpr
     * @param string|null $functionReturnType
     */
    abstract protected function addSelectColumn(
        $entityClassName,
        $tableAlias,
        $fieldName,
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
        $this->addFromStatement($this->entity, $this->tableAliases['']);
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
        foreach ($this->tableAliases as $joinId => $alias) {
            if ($joinId !== '') {
                $parentJoinId = $this->getParentJoinIdentifier($joinId);
                $this->addJoinStatement(
                    $this->tableAliases[$parentJoinId],
                    $this->getFieldName($joinId),
                    $alias
                );
            }
        }
    }

    /**
     * Performs conversion of a single JOIN statement
     *
     * @param string $joinTableAlias
     * @param string $joinFieldName
     * @param string $joinAlias
     */
    abstract protected function addJoinStatement($joinTableAlias, $joinFieldName, $joinAlias);

    /**
     * Performs conversion of WHERE statement
     */
    protected function addWhereStatement()
    {
        if (isset($this->definition['filters']) && !empty($this->definition['filters'])) {
            $groupCounter = 1;
            $this->beginWhereGroup();
            $tokens = [];
            preg_match_all(
                '/\d+|AND|OR|\(|\)/i',
                $this->definition['filters_logic'],
                $tokens,
                PREG_SET_ORDER | PREG_OFFSET_CAPTURE
            );
            $lastTokenType   = 0; // 1 - operator, 2 - filter, 3 - (, 4 - )
            $maxFilterNumber = count($this->definition['filters']);
            foreach ($tokens as $token) {
                if ($token[0][0] === '(') {
                    $this->processOpenParenthesis($groupCounter, $lastTokenType, $token);
                } elseif ($token[0][0] === ')') {
                    $this->processCloseParenthesis($groupCounter, $lastTokenType, $token);
                } elseif (false !== filter_var($token[0][0], FILTER_VALIDATE_INT)) {
                    $this->processFilter($lastTokenType, $token, $maxFilterNumber);
                } else {
                    $this->processOperator($lastTokenType, $token);
                }
            }
            $this->endWhereGroup();
            $groupCounter--;
            if ($groupCounter > 0) {
                $this->throwInvalidFilterLogicException('expecting ")" at the end');
            }
        }
    }

    /**
     * @param int   $groupCounter
     * @param int   $lastTokenType
     * @param array $token
     */
    protected function processOpenParenthesis(&$groupCounter, &$lastTokenType, &$token)
    {
        if ($lastTokenType !== 0 && $lastTokenType !== 1 && $lastTokenType !== 3) {
            $this->throwInvalidFilterLogicException(
                sprintf('unexpected "(" at position %d', $token[0][1] + 1)
            );
        }
        $this->beginWhereGroup();
        $groupCounter++;
        $lastTokenType = 3;
    }

    /**
     * @param int   $groupCounter
     * @param int   $lastTokenType
     * @param array $token
     */
    protected function processCloseParenthesis(&$groupCounter, &$lastTokenType, &$token)
    {
        if ($groupCounter <= 1 ||
            ($lastTokenType !== 0 && $lastTokenType !== 2 && $lastTokenType !== 4)
        ) {
            $this->throwInvalidFilterLogicException(
                sprintf('unexpected ")" at position %d', $token[0][1] + 1)
            );
        }
        $this->endWhereGroup();
        $groupCounter--;
        $lastTokenType = 4;
    }

    /**
     * @param int   $lastTokenType
     * @param array $token
     */
    protected function processOperator(&$lastTokenType, &$token)
    {
        if ($lastTokenType !== 2 && $lastTokenType !== 4) {
            $this->throwInvalidFilterLogicException(
                sprintf('unexpected "%s" operator at position %d', $token[0][0], $token[0][1] + 1)
            );
        }
        $this->addWhereOperator(strtoupper($token[0][0]));
        $lastTokenType = 1;
    }

    /**
     * @param int   $lastTokenType
     * @param array $token
     * @param int   $maxFilterNumber
     */
    protected function processFilter(&$lastTokenType, &$token, $maxFilterNumber)
    {
        if ($lastTokenType !== 0 && $lastTokenType !== 1 && $lastTokenType !== 3) {
            $this->throwInvalidFilterLogicException(
                sprintf('unexpected filter "%s" at position %d', $token[0][0], $token[0][1] + 1)
            );
        }
        $filterNumber = intval($token[0][0]);
        if ($filterNumber < 1 || $filterNumber > $maxFilterNumber) {
            $this->throwInvalidFilterLogicException(
                sprintf('unknown filter number "%s" at position %d', $token[0][0], $token[0][1] + 1)
            );
        }
        $filter         = $this->definition['filters'][$filterNumber - 1];
        $columnName     = $filter['columnName'];
        $fieldName      = $this->getFieldName($columnName);
        $columnAliasKey = $this->buildColumnAliasKey($columnName);
        $columnAlias    = isset($this->columnAliases[$columnAliasKey]) ? $this->columnAliases[$columnAliasKey] : null;
        $this->addWhereCondition(
            $this->getEntityClassName($columnName),
            $this->getTableAliasForColumn($columnName),
            $fieldName,
            $columnAlias,
            $filter['criterion']['filter'],
            $filter['criterion']['data']
        );
        $lastTokenType = 2;
    }

    /**
     * Raises InvalidFilterLogicException
     *
     * @param $msg
     * @throws InvalidFilterLogicException
     */
    protected function throwInvalidFilterLogicException($msg)
    {
        throw new InvalidFilterLogicException(
            sprintf('Syntax error in "%s", %s.', $this->definition['filters_logic'], $msg)
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
     * @param string $columnAlias
     * @param string $filterName
     * @param array  $filterData
     */
    abstract protected function addWhereCondition(
        $entityClassName,
        $tableAlias,
        $fieldName,
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
                $this->addGroupByColumn(
                    $this->getTableAliasForColumn($column['name']),
                    $this->getFieldName($column['name'])
                );
            }
        }
    }

    /**
     * Performs conversion of a single column of GROUP BY statement
     *
     * @param string $tableAlias
     * @param string $fieldName
     */
    abstract protected function addGroupByColumn($tableAlias, $fieldName);

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
     * Generates and saves aliases for the given joins
     *
     * @param string[] $joinIds
     */
    protected function addTableAliasesForJoinIdentifiers(array $joinIds)
    {
        foreach ($joinIds as $joinId) {
            if (!isset($this->tableAliases[$joinId])) {
                $this->tableAliases[$joinId] = sprintf(static::TABLE_ALIAS_TEMPLATE, count($this->tableAliases) + 1);
            }
        }
    }

    /**
     * Builds a join identifier for the given column
     *
     * @param string $columnName
     * @return string
     */
    protected function getJoinIdentifiers($columnName)
    {
        $lastDelimiter = strrpos($columnName, ',');
        if (false === $lastDelimiter) {
            return [''];
        }

        $result = [];
        $items  = explode(',', sprintf('%s::%s', $this->entity, substr($columnName, 0, $lastDelimiter)));
        foreach ($items as $item) {
            $result[] = empty($result)
                ? $item
                : sprintf('%s,%s', $result[count($result) - 1], $item);
        }

        return $result;
    }

    /**
     * Extracts a parent join identifier
     *
     * @param string $joinId
     * @return string
     */
    protected function getParentJoinIdentifier($joinId)
    {
        $lastDelimiter = strrpos($joinId, ',');
        if (false === $lastDelimiter) {
            return '';
        }

        return substr($joinId, 0, $lastDelimiter);
    }

    /**
     * Extracts an entity class name for the given column or from the given join identifier
     *
     * @param string $columnNameOrJoinId
     * @return string
     */
    protected function getEntityClassName($columnNameOrJoinId)
    {
        $lastDelimiter = strrpos($columnNameOrJoinId, '::');
        if (false === $lastDelimiter) {
            return $this->entity;
        }
        $lastItemDelimiter = strrpos($columnNameOrJoinId, ',');
        if (false === $lastItemDelimiter) {
            return substr($columnNameOrJoinId, 0, $lastDelimiter);
        }

        return substr($columnNameOrJoinId, $lastItemDelimiter + 1, $lastDelimiter - $lastItemDelimiter - 1);
    }

    /**
     * Extracts a field name for the given column or from the given join identifier
     *
     * @param string $columnNameOrJoinId
     * @return string
     */
    protected function getFieldName($columnNameOrJoinId)
    {
        $lastDelimiter = strrpos($columnNameOrJoinId, '::');
        if (false === $lastDelimiter) {
            return $columnNameOrJoinId;
        }

        return substr($columnNameOrJoinId, $lastDelimiter + 2);
    }

    /**
     * Returns a table alias for the given column
     *
     * @param string $columnName
     * @return string
     */
    protected function getTableAliasForColumn($columnName)
    {
        $joinId = sprintf('%s::%s', $this->entity, $columnName);
        $joinId = $this->getParentJoinIdentifier($joinId);

        return $this->tableAliases[$joinId];
    }

    /**
     * Builds a string which is used as a key of column aliases array
     *
     * @param array|string $column The column definition or name
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
     * @param string $functionExpr
     * @param string $tableAlias
     * @param string $fieldName
     * @param string $columnName
     * @param string $columnAlias
     * @return string
     */
    protected function prepareFunctionExpression($functionExpr, $tableAlias, $fieldName, $columnName, $columnAlias)
    {
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
