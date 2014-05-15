<?php

namespace Oro\Bundle\ReportBundle\Grid;

use Symfony\Bridge\Doctrine\ManagerRegistry;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\VirtualFieldProviderInterface;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\FunctionProviderInterface;
use Oro\Bundle\ReportBundle\Entity\Report;
use Oro\Bundle\DataGridBundle\Extension\Export\ExportExtension;

class ReportDatagridConfigurationBuilder extends BaseReportConfigurationBuilder
{
    /**
     * Constructor
     *
     * @param string                        $gridName
     * @param Report                        $report
     * @param FunctionProviderInterface     $functionProvider
     * @param VirtualFieldProviderInterface $virtualFieldProvider
     * @param ManagerRegistry               $doctrine
     * @param ConfigManager                 $configManager
     */
    public function __construct(
        $gridName,
        Report $report,
        FunctionProviderInterface $functionProvider,
        VirtualFieldProviderInterface $virtualFieldProvider,
        ManagerRegistry $doctrine,
        ConfigManager $configManager
    ) {
        parent::__construct($gridName, $report, $functionProvider, $virtualFieldProvider, $doctrine, $configManager);

        $this->config->offsetSetByPath('[source][acl_resource]', 'oro_report_view');
        $this->config->offsetSetByPath(ExportExtension::EXPORT_OPTION_PATH, true);
    }
}
