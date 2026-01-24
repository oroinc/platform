<?php

namespace Oro\Bundle\QueryDesignerBundle\QueryDesigner;

/**
 * Defines the contract for query designer aggregation and transformation functions.
 *
 * Implementations of this interface represent functions that can be applied to columns
 * in query designer queries, such as aggregation functions (`COUNT`, `SUM`, `AVG`) or
 * transformation functions. Each function generates a SQL expression that is integrated
 * into the query builder. Implementations must handle table aliases, field names, and
 * column aliases to produce correct SQL expressions for the target database.
 */
interface FunctionInterface
{
    /**
     * Returns a string represents a function expression
     *
     * @param string                 $tableAlias     Table alias
     * @param string                 $fieldName      Field name
     * @param string                 $columnName     Full column name including table alias
     * @param string                 $columnAlias    Column alias
     * @param AbstractQueryConverter $queryConverter The query converter
     * @return string
     */
    public function getExpression(
        $tableAlias,
        $fieldName,
        $columnName,
        $columnAlias,
        AbstractQueryConverter $queryConverter
    );
}
