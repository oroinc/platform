<?php

namespace Oro\Bundle\ChartBundle\Model;

use Doctrine\DBAL\Types\Types;
use Oro\Bundle\DashboardBundle\Helper\DateHelper;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\EntityBundle\Provider\EntityFieldProvider;
use Oro\Bundle\ReportBundle\Entity\Report;

/**
 * Builds parameters for charts.
 */
class ChartOptionsBuilder
{
    public function __construct(
        private EntityFieldProvider $fieldProvider,
        private DateHelper $dateHelper,
        private Report $report,
        private DatagridInterface $datagrid
    ) {
    }

    public function buildChartOptions(): array
    {
        $chartOptions = $this->report->getChartOptions();
        $this
            ->buildOptionsAliases($chartOptions)
            ->buildOptionsTypes($chartOptions)
            ->buildDateOptions($chartOptions);

        return $chartOptions;
    }

    private function buildOptionsAliases(array &$chartOptions): self
    {
        $gridConfig = $this->datagrid->getConfig()->toArray();
        if (isset($gridConfig['source']['query_config']['column_aliases'])) {
            $columnAliases = $gridConfig['source']['query_config']['column_aliases'];
            $dataSchema = array_map(fn ($value) => $columnAliases[$value] ?? $value, $chartOptions['data_schema']);
            $chartOptions['original_data_schema'] = array_combine(
                array_values($dataSchema),
                array_values($chartOptions['data_schema'])
            );
            $chartOptions['data_schema'] = $dataSchema;
        }

        return $this;
    }

    private function buildOptionsTypes(array &$chartOptions): self
    {
        $className = $this->report->getEntity();
        $chartOptions['field_types'] = array_reduce(
            $this->fieldProvider->getEntityFields($className, EntityFieldProvider::OPTION_WITH_VIRTUAL_FIELDS),
            function ($accum, $item) {
                $accum[$item['name']] = $item['type'];

                return $accum;
            }
        );

        return $this;
    }

    /**
     * Method detects a type of report's chart 'label' field, and in case of datetime will check a date interval and
     * set a proper type (time, day, date, month or year). Xaxis labels are not taken into account - they will
     * be rendered automatically.
     * Also, chart dot labels may overlap if dates are close to each other.
     * Should be refactored in the scope of BAP-8294.
     */
    protected function buildDateOptions(array &$chartOptions): self
    {
        $labelFieldName = $chartOptions['data_schema']['label'];
        $labelFieldType = $this->datagrid->getConfig()->offsetGetByPath(
            sprintf('[columns][%s][frontend_type]', $labelFieldName)
        );

        $dateTypes = [Types::DATETIME_MUTABLE, Types::DATE_MUTABLE, Types::DATETIMETZ_MUTABLE];
        if (in_array($labelFieldType, $dateTypes)) {
            $data = $this->datagrid->getData()->offsetGet('data');
            $dates = array_map(fn ($dateItem) => $dateItem[$labelFieldName], $data);
            $minDate = new \DateTime(min($dates));
            $maxDate = new \DateTime(max($dates));

            $formatStrings = $this->dateHelper->getFormatStrings($minDate, $maxDate);
            $chartOptions['data_schema']['label'] = [
                'field_name' => $chartOptions['data_schema']['label'],
                'type' => $formatStrings['viewType']
            ];
        }

        return $this;
    }
}
