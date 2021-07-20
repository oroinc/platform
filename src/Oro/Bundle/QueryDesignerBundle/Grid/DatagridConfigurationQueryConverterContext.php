<?php

namespace Oro\Bundle\QueryDesignerBundle\Grid;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\GroupingOrmQueryConverterContext;

/**
 * The context for {@see \Oro\Bundle\QueryDesignerBundle\Grid\DatagridConfigurationQueryConverter}.
 */
class DatagridConfigurationQueryConverterContext extends GroupingOrmQueryConverterContext
{
    /** @var DatagridConfiguration */
    private $config;

    /** @var string[] */
    private $selectColumns = [];

    /** @var string[] */
    private $groupingColumns = [];

    /** @var array[] [['table' => entity class name, 'alias' => entity alias], ...] */
    private $from = [];

    /** @var array[] [['join' => join, 'alias' => alias, 'conditionType' => optional, 'condition' => optional], ...] */
    private $innerJoins = [];

    /** @var array[] [['join' => join, 'alias' => alias, 'conditionType' => optional, 'condition' => optional], ...] */
    private $leftJoins = [];

    /**
     * {@inheritDoc}
     */
    public function reset(): void
    {
        parent::reset();
        $this->config = null;
        $this->selectColumns = [];
        $this->groupingColumns = [];
        $this->from = [];
        $this->innerJoins = [];
        $this->leftJoins = [];
    }

    public function getConfig(): DatagridConfiguration
    {
        return $this->config;
    }

    public function setConfig(DatagridConfiguration $config): void
    {
        $this->config = $config;
    }

    /**
     * @return string[]
     */
    public function getSelectColumns(): array
    {
        return $this->selectColumns;
    }

    public function addSelectColumn(string $column): void
    {
        $this->selectColumns[] = $column;
    }

    /**
     * @return string[]
     */
    public function getGroupingColumns(): array
    {
        return $this->groupingColumns;
    }

    public function addGroupingColumn(string $column): void
    {
        $this->groupingColumns[] = $column;
    }

    /**
     * @return array[] [['table' => entity class name, 'alias' => entity alias], ...]
     */
    public function getFrom(): array
    {
        return $this->from;
    }

    public function addFrom(string $entityClass, string $tableAlias): void
    {
        $this->from[] = ['table' => $entityClass, 'alias' => $tableAlias];
    }

    /**
     * @return array[] [['join' => join, 'alias' => alias, 'conditionType' => optional, 'condition' => optional], ...]
     */
    public function getInnerJoins(): array
    {
        return $this->innerJoins;
    }

    public function addInnerJoin(
        string $join,
        string $alias,
        string $conditionType = null,
        string $condition = null
    ): void {
        $this->innerJoins[] = $this->buildJoin($join, $alias, $conditionType, $condition);
    }

    /**
     * @return array[] [['join' => join, 'alias' => alias, 'conditionType' => optional, 'condition' => optional], ...]
     */
    public function getLeftJoins(): array
    {
        return $this->leftJoins;
    }

    public function addLeftJoin(
        string $join,
        string $alias,
        string $conditionType = null,
        string $condition = null
    ): void {
        $this->leftJoins[] = $this->buildJoin($join, $alias, $conditionType, $condition);
    }

    private function buildJoin(string $join, string $alias, ?string $conditionType, ?string $condition): array
    {
        $result = ['join' => $join, 'alias' => $alias];
        if ($conditionType) {
            $result['conditionType'] = $conditionType;
        }
        if ($condition) {
            $result['condition'] = $condition;
        }

        return $result;
    }
}
