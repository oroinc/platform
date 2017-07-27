<?php

namespace Oro\Bundle\ReportBundle\Grid;

use Oro\Bundle\DataGridBundle\Extension\Export\ExportExtension;
use Oro\Bundle\EntityPaginationBundle\Datagrid\EntityPaginationExtension;

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
