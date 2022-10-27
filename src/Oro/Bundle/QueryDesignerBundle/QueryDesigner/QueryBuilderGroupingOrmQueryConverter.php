<?php

namespace Oro\Bundle\QueryDesignerBundle\QueryDesigner;

/**
 * Provides a base functionality to convert a query definition created by the query designer to an ORM query builder.
 */
abstract class QueryBuilderGroupingOrmQueryConverter extends GroupingOrmQueryConverter
{
    /**
     * {@inheritdoc}
     */
    protected function createContext(): QueryBuilderGroupingOrmQueryConverterContext
    {
        return new QueryBuilderGroupingOrmQueryConverterContext();
    }

    /**
     * {@inheritdoc}
     */
    protected function context(): QueryBuilderGroupingOrmQueryConverterContext
    {
        return parent::context();
    }

    /**
     * {@inheritdoc}
     */
    protected function addFromStatement(string $entityClass, string $tableAlias): void
    {
        $this->context()->getQueryBuilder()->from($entityClass, $tableAlias);
    }

    /**
     * {@inheritdoc}
     */
    protected function addJoinStatement(
        ?string $joinType,
        string $join,
        string $joinAlias,
        ?string $joinConditionType,
        ?string $joinCondition
    ): void {
        if (self::LEFT_JOIN === $joinType) {
            $this->context()->getQueryBuilder()->leftJoin($join, $joinAlias, $joinConditionType, $joinCondition);
        } else {
            $this->context()->getQueryBuilder()->innerJoin($join, $joinAlias, $joinConditionType, $joinCondition);
        }
    }
}
