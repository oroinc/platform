<?php

namespace Oro\Bundle\SegmentBundle\Query;

use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\AbstractOrmQueryConverter;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\FunctionInterface;

class SegmentQueryConverter extends AbstractOrmQueryConverter
{
    public function convert(Segment $segment)
    {

    }

    /**
     * Performs conversion of a single column of SELECT statement
     *
     * @param string                        $entityClassName
     * @param string                        $tableAlias
     * @param string                        $fieldName
     * @param string                        $columnAlias
     * @param string                        $columnLabel
     * @param string|FunctionInterface|null $functionExpr
     * @param string|null                   $functionReturnType
     */
    protected function addSelectColumn(
        $entityClassName,
        $tableAlias,
        $fieldName,
        $columnAlias,
        $columnLabel,
        $functionExpr,
        $functionReturnType
    ) {
        // TODO: Implement addSelectColumn() method.
    }

    /**
     * Performs conversion of a single table of FROM statement
     *
     * @param string $entityClassName
     * @param string $tableAlias
     */
    protected function addFromStatement($entityClassName, $tableAlias)
    {
        // TODO: Implement addFromStatement() method.
    }

    /**
     * Performs conversion of a single JOIN statement
     *
     * @param string $joinTableAlias
     * @param string $joinFieldName
     * @param string $joinAlias
     */
    protected function addJoinStatement($joinTableAlias, $joinFieldName, $joinAlias)
    {
        // TODO: Implement addJoinStatement() method.
    }

    /**
     * Opens new group in WHERE statement
     */
    protected function beginWhereGroup()
    {
        // TODO: Implement beginWhereGroup() method.
    }

    /**
     * Closes current group in WHERE statement
     */
    protected function endWhereGroup()
    {
        // TODO: Implement endWhereGroup() method.
    }

    /**
     * Adds an operator to WHERE condition
     *
     * @param string $operator An operator. Can be AND or OR
     */
    protected function addWhereOperator($operator)
    {
        // TODO: Implement addWhereOperator() method.
    }

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
    protected function addWhereCondition(
        $entityClassName,
        $tableAlias,
        $fieldName,
        $columnAlias,
        $filterName,
        array $filterData
    ) {
        // TODO: Implement addWhereCondition() method.
    }

    /**
     * Performs conversion of a single column of GROUP BY statement
     *
     * @param string $tableAlias
     * @param string $fieldName
     */
    protected function addGroupByColumn($tableAlias, $fieldName)
    {
        // do nothing, grouping is not allowed
    }

    /**
     * {@inheritdoc}
     */
    protected function addOrderByColumn($columnAlias, $columnSorting)
    {
        // do nothing, order could not change results
    }
}
