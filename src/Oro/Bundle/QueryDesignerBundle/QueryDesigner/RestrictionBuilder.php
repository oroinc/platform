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

    /**
     * @param Manager $manager
     * @param ConfigManager $configManager
     */
    public function __construct(Manager $manager, ConfigManager $configManager)
    {
        $this->manager = $manager;
        $this->configManager = $configManager;
    }

    /**
     * @param FilterExecutionContext $filterExecutionContext
     */
    public function setFilterExecutionContext(FilterExecutionContext $filterExecutionContext)
    {
        $this->filterExecutionContext = $filterExecutionContext;
    }

    /**
     * {@inheritdoc}
     */
    public function buildRestrictions(array $filters, GroupingOrmFilterDatasourceAdapter $ds)
    {
        $this->doBuildRestrictions($filters, $ds);
        $ds->applyRestrictions();
    }

    /**
     * Recursive iterates through filters and builds an expression to be applied to the given data source
     *
     * @param array $filters
     * @param GroupingOrmFilterDatasourceAdapter $ds
     */
    protected function doBuildRestrictions(array $filters, GroupingOrmFilterDatasourceAdapter $ds)
    {
        $operatorStack = [FilterUtility::CONDITION_AND];
        $isInGroup = !empty($filters['in_group']);
        unset($filters['in_group']);

        foreach ($filters as $item) {
            if (is_string($item)) {
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

    /**
     * @param GroupingOrmFilterDatasourceAdapter $ds
     * @param string $operator
     * @param array $item
     */
    protected function buildGroupedRestrictions(GroupingOrmFilterDatasourceAdapter $ds, $operator, array $item)
    {
        if (count($item) === 1) {
            // Skip nested conditions groups without filters
            $this->doBuildRestrictions($item, $ds);
        } else {
            // Process conditions group
            $ds->beginRestrictionGroup($operator);

            if ($this->isConditionsGroupingEnabled()) {
                // Group toMany under same EXISTS and filter the main Query by it
                $conditionsGroupFilter = $this->manager->getFilter('conditions_group');
                $conditionsGroupFilter->apply($ds, ['filters' => $item]);
            } else {
                $this->doBuildRestrictions($item, $ds);
            }

            $ds->endRestrictionGroup();
        }
    }

    /**
     * @param GroupingOrmFilterDatasourceAdapter $ds
     * @param string $operator
     * @param array $item
     * @param bool $isInGroup
     */
    protected function buildSingleRestriction(
        GroupingOrmFilterDatasourceAdapter $ds,
        $operator,
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
     * Returns prepared filter object.
     *
     * @param string $name A filter name.
     * @param string $columnName A column name this filter should be applied.
     * @param array $params The filter parameters.
     *
     * @return FilterInterface
     */
    protected function getFilterObject($name, $columnName, array $params = [])
    {
        $params[FilterUtility::DATA_NAME_KEY] = $columnName;

        return $this->manager->createFilter($name, $params);
    }

    /**
     * @return bool
     */
    private function isConditionsGroupingEnabled(): bool
    {
        if (null === $this->conditionsGroupingEnabled) {
            $this->conditionsGroupingEnabled = (bool)$this->configManager
                ->get('oro_query_designer.conditions_group_merge_same_entity_conditions');
        }

        return $this->conditionsGroupingEnabled;
    }
}
