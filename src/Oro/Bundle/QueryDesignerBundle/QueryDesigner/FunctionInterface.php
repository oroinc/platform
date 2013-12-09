<?php

namespace Oro\Bundle\QueryDesignerBundle\QueryDesigner;

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
