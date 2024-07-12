<?php

namespace Oro\Bundle\ReportBundle\Grid;

use Oro\Bundle\DataGridBundle\Extension\Export\ExportExtension;
use Oro\Bundle\EntityPaginationBundle\Datagrid\EntityPaginationExtension;

/**
 * Enables entity pagination and grid export for report grids.
 * Results of the builder is cached by ReportDatagridConfigurationProvider.
 */
class ReportDatagridConfigurationBuilder extends BaseReportConfigurationBuilder
{
    /**
     * @var DatagridDateGroupingBuilder
     */
    protected $dateGroupingBuilder;

    /**
     * {@inheritdoc}
     */
    public function getConfiguration()
    {
        $config = parent::getConfiguration();

        $config->offsetSetByPath('[source][acl_resource]', 'oro_report_view');
        $config->offsetSetByPath(ExportExtension::EXPORT_OPTION_PATH, true);
        $config->offsetSetByPath(EntityPaginationExtension::ENTITY_PAGINATION_PATH, true);
        if (!in_array('HINT_TRANSLATABLE', $config->offsetGetByPath('[source][hints]', []))) {
            $config->offsetAddToArrayByPath('[source][hints]', ['HINT_TRANSLATABLE']);
        }

        $this->dateGroupingBuilder->applyDateGroupingFilterIfRequired($config, $this->source);

        return $config;
    }

    /**
     * @param DatagridDateGroupingBuilder $dateGroupingBuilder
     * @return $this
     */
    public function setDateGroupingBuilder(DatagridDateGroupingBuilder $dateGroupingBuilder)
    {
        $this->dateGroupingBuilder = $dateGroupingBuilder;

        return $this;
    }
}
