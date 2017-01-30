<?php

namespace Oro\Bundle\ReportBundle\EventListener;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Extension\Sorter\AbstractSorterExtension;
use Oro\Bundle\FilterBundle\Filter\DateGroupingFilter;
use Oro\Bundle\FilterBundle\Filter\SkipEmptyPeriodsFilter;
use Oro\Bundle\FilterBundle\Form\Type\Filter\DateGroupingFilterType;
use Oro\Bundle\QueryDesignerBundle\Form\Type\AbstractQueryDesignerType;
use Oro\Bundle\QueryDesignerBundle\Form\Type\DateGroupingType;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\JoinIdentifierHelper;
use Oro\Bundle\ReportBundle\Entity\Report;
use Oro\Bundle\ReportBundle\Event\AfterBuildGridConfigurationEvent;
use Oro\Bundle\ReportBundle\Exception\InvalidDatagridConfigException;

class ReportGridConfigurationListener
{
    const FILTERS_KEY_NAME = 'filters';
    const SOURCE_KEY_NAME = 'source';
    const SORTERS_KEY_NAME = 'sorters';
    const FIELDS_ACL_KEY_NAME = 'fields_acl';
    const COLUMNS_KEY_NAME = 'columns';
    const CALENDAR_DATE_COLUMN_ALIAS = 'cDate';
    const CALENDAR_TABLE_JOIN_CONDITION_TEMPLATE = 'CAST(%s as DATE) = CAST(%s.%s as DATE)';
    const CALENDAR_DATE_GRID_COLUMN_NAME = 'dateGrouping';
    const DATE_PERIOD_FILTER = 'datePeriodFilter';

    /**
     * @var string
     */
    protected $calendarDateClass;

    /**
     * @var JoinIdentifierHelper
     */
    protected $joinIdentifierHelper;

    /**
     * @param string $calendarDateEntity
     */
    public function __construct($calendarDateEntity)
    {
        $this->calendarDateClass = $calendarDateEntity;
        $this->joinIdentifierHelper = new JoinIdentifierHelper($this->calendarDateClass);
    }

    /**
     * @param AfterBuildGridConfigurationEvent $event
     * @throws InvalidDatagridConfigException
     */
    public function onReportGridConfigurationBuild(AfterBuildGridConfigurationEvent $event)
    {
        $config = $event->getConfiguration();
        $report = $event->getSource();

        if (!$config instanceof DatagridConfiguration
            || !$report instanceof Report
        ) {
            return;
        }

        $reportDefinition = json_decode($report->getDefinition(), true);
        if (!$this->isDateGroupingFilterRequired($reportDefinition)) {
            return;
        }

        if (!$this->isGridConfigValid($config)) {
            throw new InvalidDatagridConfigException();
        }

        $dateGroupDefinition = $reportDefinition[AbstractQueryDesignerType::DATE_GROUPING_NAME];
        $notNullableField = $this->getRealNotNullableField($config);

        $this->changeFiltersSection(
            $config,
            $notNullableField,
            $event->getSource()->getEntity(),
            $dateGroupDefinition[DateGroupingType::FIELD_NAME_ID]
        );
        $this->changeSourceSection($config, $dateGroupDefinition[DateGroupingType::FIELD_NAME_ID]);
        $this->changeSortersSection($config);
        $this->changeColumnsSection($config);
        $this->addEmptyPeriodsFilter(
            $config,
            $dateGroupDefinition[DateGroupingType::USE_SKIP_EMPTY_PERIODS_FILTER_ID],
            $notNullableField
        );
    }

    /**
     * @param DatagridConfiguration $config
     * @param string $notNullableField
     * @param string $rootEntityClass
     * @param string $dateGroupinFieldName
     * @param string $defaultFilterValue
     */
    protected function changeFiltersSection(
        DatagridConfiguration $config,
        $notNullableField,
        $rootEntityClass,
        $dateGroupinFieldName,
        $defaultFilterValue = DateGroupingFilterType::TYPE_DAY
    ) {
        $filters = $config->offsetGet(static::FILTERS_KEY_NAME);

        $filters['columns'][DateGroupingFilter::NAME] = [
            'type' => DateGroupingFilter::NAME,
            'data_name' => $this->getCalendarDateFieldReferenceString(),
            'label' => 'oro.report.filter.grouping.label',
            'column_name' => static::CALENDAR_DATE_GRID_COLUMN_NAME,
            'calendar_entity' => $this->calendarDateClass,
            'target_entity' => $rootEntityClass,
            'not_nullable_field' => $notNullableField,
        ];
        $dateGroupinFieldAlias = $this->getColumnAlias($config, $dateGroupinFieldName);
        if (!array_key_exists(static::DATE_PERIOD_FILTER, $filters['columns'])
            && (
                is_null($dateGroupinFieldAlias)
                || !array_key_exists($dateGroupinFieldAlias, $filters['columns'])
            )
        ) {
            $filters['columns'][static::DATE_PERIOD_FILTER] = [
                'label' => 'oro.report.datagrid.column.time_period.label',
                'type' => 'datetime',
                'data_name' => $this->getCalendarDateFieldReferenceString(),
            ];
        }

        if (!array_key_exists('default', $filters)) {
            $filters['default'] = [];
        }
        $filters['default'][DateGroupingFilter::NAME] = ['value' => $defaultFilterValue];

        $config->offsetSet(static::FILTERS_KEY_NAME, $filters);
    }

    /**
     * @param DatagridConfiguration $config
     * @param bool $useSkipEmptyPeriodsFilter
     * @param string $notNullableField
     */
    protected function addEmptyPeriodsFilter(
        DatagridConfiguration $config,
        $useSkipEmptyPeriodsFilter,
        $notNullableField
    ) {
        if (!$useSkipEmptyPeriodsFilter) {
            return;
        }
        $filters = $config->offsetGet(static::FILTERS_KEY_NAME);
        $filters['columns'][SkipEmptyPeriodsFilter::NAME] = [
            'type' => SkipEmptyPeriodsFilter::NAME,
            'data_name' => $this->getCalendarDateFieldReferenceString(),
            'label' => 'oro.report.filter.skip_empty_periods.label',
            'not_nullable_field' => $notNullableField,
        ];
        $filters['default'][SkipEmptyPeriodsFilter::NAME] = ['value' => true];
        $config->offsetSet(static::FILTERS_KEY_NAME, $filters);
    }

    /**
     * @param DatagridConfiguration $config
     */
    protected function changeColumnsSection(DatagridConfiguration $config)
    {
        $columns = $config->offsetGet(static::COLUMNS_KEY_NAME);
        $newColumns = [
            static::CALENDAR_DATE_COLUMN_ALIAS => [
                'frontend_type' => 'date',
                'renderable' => false,
            ],
            static::CALENDAR_DATE_GRID_COLUMN_NAME => [
                'label' => 'oro.report.datagrid.column.time_period.label',
            ],
        ];
        $columns = $newColumns + $columns;
        $config->offsetSet(static::COLUMNS_KEY_NAME, $columns);
    }

    /**
     * @param DatagridConfiguration $config
     */
    protected function changeSortersSection(DatagridConfiguration $config)
    {
        $sorters = $config->offsetGet(static::SORTERS_KEY_NAME);
        $sorters['columns'][static::CALENDAR_DATE_COLUMN_ALIAS] = [
            'data_name' => $this->getCalendarDateFieldReferenceString(),

        ];
        if (!array_key_exists('default', $sorters)) {
            $sorters['default'] = [];
        }
        $sorters['default'][static::CALENDAR_DATE_COLUMN_ALIAS] = AbstractSorterExtension::DIRECTION_DESC;
        $config->offsetSet(static::SORTERS_KEY_NAME, $sorters);
    }

    /**
     * @param DatagridConfiguration $config
     * @param string $dateFieldName
     */
    protected function changeSourceSection(DatagridConfiguration $config, $dateFieldName)
    {
        $source = $config->offsetGet(static::SOURCE_KEY_NAME);
        $from = $source['query']['from'][0];
        $newFrom = [
            'alias' => DateGroupingFilter::CALENDAR_TABLE,
            'table' => $this->calendarDateClass,
        ];
        $source['query']['from'][0] = $newFrom;
        if (!array_key_exists('join', $source['query'])) {
            $source['query']['join'] = [];
        }
        if (!array_key_exists('left', $source['query']['join'])) {
            $source['query']['join']['left'] = [];
        }
        $newLeftJoins = [];
        $newLeftJoins[] = [
            'join' => $from['table'],
            'alias' => $from['alias'],
            'conditionType' => 'WITH',
            'condition' => $this->getCalendarJoinCondition($from['alias'], $dateFieldName),
        ];
        foreach ($source['query']['join']['left'] as $join) {
            $newLeftJoins[] = $join;
        }
        $source['query']['join']['left'] = $newLeftJoins;
        $source['query']['select'][] = $this->getCalenderSelectProperty();
        $config->offsetSet(static::SOURCE_KEY_NAME, $source);
    }

    /**
     * @param string $rootEntityAlias
     * @param string $rootEntityDateFieldName
     * @return string
     */
    protected function getCalendarJoinCondition($rootEntityAlias, $rootEntityDateFieldName)
    {
        return sprintf(
            static::CALENDAR_TABLE_JOIN_CONDITION_TEMPLATE,
            $this->getCalendarDateFieldReferenceString(),
            $rootEntityAlias,
            $rootEntityDateFieldName
        );
    }

    /**
     * @return string
     */
    protected function getCalendarDateFieldReferenceString()
    {
        return sprintf('%s.date', DateGroupingFilter::CALENDAR_TABLE);
    }

    /**
     * @return string
     */
    protected function getCalenderSelectProperty()
    {
        return sprintf(
            '%s as %s',
            $this->getCalendarDateFieldReferenceString(),
            static::CALENDAR_DATE_COLUMN_ALIAS
        );
    }

    /**
     * @param DatagridConfiguration $config
     * @return bool
     */
    protected function isGridConfigValid(DatagridConfiguration $config)
    {
        return ($config->offsetExists(static::SOURCE_KEY_NAME)
            && isset($config->offsetGet(static::SOURCE_KEY_NAME)['query_config'])
            && isset($config->offsetGet(static::SOURCE_KEY_NAME)['query_config']['column_aliases'])
            && isset($config->offsetGet(static::SOURCE_KEY_NAME)['query_config']['table_aliases'])
            && isset($config->offsetGet(static::SOURCE_KEY_NAME)['query'])
            && isset($config->offsetGet(static::SOURCE_KEY_NAME)['query']['select'])
            && isset($config->offsetGet(static::SOURCE_KEY_NAME)['query']['groupBy'])
            && isset($config->offsetGet(static::SOURCE_KEY_NAME)['query']['from'])
            && count($config->offsetGet(static::SOURCE_KEY_NAME)['query']['from']) > 0
            && $config->offsetExists(static::SORTERS_KEY_NAME)
            && isset($config->offsetGet(static::SORTERS_KEY_NAME)['columns'])
            && $config->offsetExists(static::FIELDS_ACL_KEY_NAME)
            && isset($config->offsetGet(static::FIELDS_ACL_KEY_NAME)['columns'])
            && $config->offsetExists(static::COLUMNS_KEY_NAME)
            && $config->offsetExists(static::FILTERS_KEY_NAME)
            && isset($config->offsetGet(static::FILTERS_KEY_NAME)['columns'])
        );
    }

    /**
     * @param array $definition
     * @return bool
     */
    protected function isDateGroupingFilterRequired($definition)
    {
        return (is_array($definition)
            && array_key_exists(AbstractQueryDesignerType::DATE_GROUPING_NAME, $definition)
            && array_key_exists(
                DateGroupingType::FIELD_NAME_ID,
                $definition[AbstractQueryDesignerType::DATE_GROUPING_NAME]
            )
            && array_key_exists(
                DateGroupingType::USE_SKIP_EMPTY_PERIODS_FILTER_ID,
                $definition[AbstractQueryDesignerType::DATE_GROUPING_NAME]
            )
        );
    }

    /**
     * @param DatagridConfiguration $config
     * @return string
     * @throws InvalidDatagridConfigException
     */
    protected function getRealNotNullableField(DatagridConfiguration $config)
    {
        $source = $config->offsetGet(static::SOURCE_KEY_NAME);
        $groupBy = explode(',', $source['query']['groupBy']);

        return reset($groupBy);
    }

    /**
     * @param DatagridConfiguration $config
     * @param string $fieldName
     * @return null
     */
    protected function getColumnAlias(DatagridConfiguration $config, $fieldName)
    {
        $aliases = $config->offsetGet(static::SOURCE_KEY_NAME)['query_config']['column_aliases'];

        return (array_key_exists($fieldName, $aliases)) ? $aliases[$fieldName] : null;
    }
}
