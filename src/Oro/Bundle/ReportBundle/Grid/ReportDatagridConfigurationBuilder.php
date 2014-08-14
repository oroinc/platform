<?php

namespace Oro\Bundle\ReportBundle\Grid;

use Oro\Bundle\DataGridBundle\Extension\Export\ExportExtension;

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

        return $config;
    }
}
