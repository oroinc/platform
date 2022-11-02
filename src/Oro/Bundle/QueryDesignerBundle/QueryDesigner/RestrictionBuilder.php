<?php

namespace Oro\Bundle\QueryDesignerBundle\QueryDesigner;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\FilterBundle\Filter\FilterExecutionContext;
use Oro\Bundle\FilterBundle\Filter\FilterInterface;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\QueryDesignerBundle\Grid\Extension\GroupingOrmFilterDatasourceAdapter;

/**
 * Applies the query designer filters to a data source.
 */
class RestrictionBuilder implements RestrictionBuilderInterface
{
    /** @var Manager */
    protected $manager;

    /** @var ConfigManager */
    protected $configManager;

    /** @var FilterExecutionContext */
    private $filterExecutionContext;

    /** @var bool|null */
    private $conditionsGroupingEnabled;

    /** @var ConditionsGroupBuilder|null */
    private $conditionsGroupBuilder;

    public function __construct(
        Manager $manager,
        ConfigManager $configManager,
        FilterExecutionContext $filterExecutionContext
    ) {
        $this->manager = $manager;
        $this->configManager = $configManager;
        $this->filterExecutionContext = $filterExecutionContext;
    }

    /**
     * {@inheritdoc}
     */
    public function buildRestrictions(array $filters, GroupingOrmFilterDatasourceAdapter $ds): void
    {
        $this->doBuildRestrictions($filters, $ds);
        $ds->applyRestrictions();
    }

    /**
     * Recursive iterates through filters and builds an expression to be applied to the given data source.
     */
    protected function doBuildRestrictions(array $filters, GroupingOrmFilterDatasourceAdapter $ds): void
    {
        $operatorStack = [FilterUtility::CONDITION_AND];
        $isInGroup = !empty($filters['in_group']);
        unset($filters['in_group']);

        foreach ($filters as $item) {
            if (\is_string($item)) {
                $operatorStack[] = $item;
                continue;
            }

            $operator = array_pop($operatorStack);
            if (isset($item['filter'])) {
                $this->buildSingleRestriction($ds, $operator, $item, $isInGroup);
            } else {
                $this->buildGroupedRestrictions($ds, $operator, $item);
            }
        }
    }

    protected function buildGroupedRestrictions(
        GroupingOrmFilterDatasourceAdapter $ds,
        string $operator,
        array $item
    ): void {
        if (count($item) === 1) {
            // skip nested conditions group without filters
            $this->doBuildRestrictions($item, $ds);
        } else {
            // process conditions group
            $ds->beginRestrictionGroup($operator);
            if ($this->isConditionsGroupingEnabled()) {
                // group toMany under same EXISTS and filter the main query by it
                $this->getConditionsGroupBuilder()->apply($this, $ds, $item);
            } else {
                $this->doBuildRestrictions($item, $ds);
            }
            $ds->endRestrictionGroup();
        }
    }

    protected function buildSingleRestriction(
        GroupingOrmFilterDatasourceAdapter $ds,
        string $operator,
        array $item,
        bool $isInGroup
    ): void {
        $data = $item['filterData'];
        $params = null;
        if (isset($data['params'])) {
            $params = $data['params'];
            unset($data['params']);
        }
        $filter = $this->getFilterObject(
            $item['filter'],
            $item['column'],
            $params ?? [FilterUtility::CONDITION_KEY => $operator]
        );

        $normalizedData = $this->filterExecutionContext->normalizedFilterData($filter, $data);
        if (null !== $normalizedData) {
            if (!isset($normalizedData['in_group'])) {
                $normalizedData['in_group'] = $isInGroup;
            }
            $ds->beginRestrictionGroup($operator);
            $filter->apply($ds, $normalizedData);
            $ds->endRestrictionGroup();
        }
    }

    /**
     * @param string $name       The name of a filter
     * @param string $columnName The name of a column this filter should be applied
     * @param array  $params     The parameters of a filter
     *
     * @return FilterInterface
     */
    protected function getFilterObject(string $name, string $columnName, array $params = []): FilterInterface
    {
        $params[FilterUtility::DATA_NAME_KEY] = $columnName;

        return $this->manager->createFilter($name, $params);
    }

    protected function getConditionsGroupBuilder(): ConditionsGroupBuilder
    {
        if (null === $this->conditionsGroupBuilder) {
            $this->conditionsGroupBuilder = new ConditionsGroupBuilder();
        }

        return $this->conditionsGroupBuilder;
    }

    protected function isConditionsGroupingEnabled(): bool
    {
        if (null === $this->conditionsGroupingEnabled) {
            $this->conditionsGroupingEnabled = (bool)$this->configManager
                ->get('oro_query_designer.conditions_group_merge_same_entity_conditions');
        }

        return $this->conditionsGroupingEnabled;
    }
}
