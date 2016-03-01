<?php

namespace Oro\Bundle\ReportBundle\Grid;

use Oro\Bundle\DataGridBundle\Extension\Export\ExportExtension;
use Oro\Bundle\EntityPaginationBundle\Datagrid\EntityPaginationExtension;

class ReportDatagridConfigurationBuilder extends BaseReportConfigurationBuilder
{
    /**
     * {@inheritdoc}
     */
    public function getConfiguration()
    {
        $config = parent::getConfiguration();

        $config->offsetSetByPath('[source][acl_resource]', 'oro_report_view');
        $config->offsetSetByPath(ExportExtension::EXPORT_OPTION_PATH, true);
        $config->offsetSetByPath(EntityPaginationExtension::ENTITY_PAGINATION_PATH, true);

        return $config;
    }
}
