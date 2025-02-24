<?php

namespace Oro\Bundle\QueryDesignerBundle\QueryDesigner;

/**
 * Provides a base functionality to convert a query definition created by the query designer to an ORM query.
 */
abstract class GroupingOrmQueryConverter extends AbstractOrmQueryConverter
{
    #[\Override]
    protected function createContext(): GroupingOrmQueryConverterContext
    {
        return new GroupingOrmQueryConverterContext();
    }

    #[\Override]
    protected function context(): GroupingOrmQueryConverterContext
    {
        return parent::context();
    }

    #[\Override]
    protected function beginWhereGroup(): void
    {
        $this->context()->beginFilterGroup();
    }

    #[\Override]
    protected function endWhereGroup(): void
    {
        $this->context()->endFilterGroup();
    }

    #[\Override]
    protected function addWhereOperator(string $operator): void
    {
        $this->context()->addFilterOperator($operator);
    }

    #[\Override]
    protected function addWhereCondition(
        string $entityClass,
        string $tableAlias,
        string $fieldName,
        string $columnExpr,
        ?string $columnAlias,
        string $filterName,
        array $filterData,
        $functionExpr = null
    ): void {
        $filter = [
            'column'     => $this->getFilterByExpr(
                $entityClass,
                $tableAlias,
                $fieldName,
                $functionExpr
                ? $this->prepareFunctionExpression(
                    $functionExpr,
                    $tableAlias,
                    $fieldName,
                    $columnExpr,
                    $columnAlias
                )
                : $columnExpr
            ),
            'filter'     => $filterName,
            'filterData' => $filterData
        ];
        if ($columnAlias) {
            $filter['columnAlias'] = $columnAlias;
        }
        $this->context()->addFilter($filter);
    }

    protected function getFilterByExpr(
        string $entityClass,
        string $tableAlias,
        string $fieldName,
        string $columnExpr
    ): string {
        $filterById = false;
        if ($entityClass && $this->isVirtualField($entityClass, $fieldName)) {
            $columnJoinId = $this->buildColumnJoinIdentifier($fieldName, $entityClass);
            if ($this->context()->hasVirtualColumnOption($columnJoinId, 'filter_by_id')
                && $this->context()->getVirtualColumnOption($columnJoinId, 'filter_by_id')
            ) {
                $filterById = true;
            }
        }

        return $filterById
            ? sprintf('%s.%s', $tableAlias, $fieldName)
            : $columnExpr;
    }
}
