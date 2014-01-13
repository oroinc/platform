<?php

namespace Oro\Bundle\ReportBundle\Grid;

use Oro\Bundle\DataGridBundle\Extension\Toolbar\ToolbarExtension;
use Symfony\Bridge\Doctrine\ManagerRegistry;
use Oro\Bundle\QueryDesignerBundle\Grid\DatagridConfigurationBuilder;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\FunctionProviderInterface;
use Oro\Bundle\ReportBundle\Entity\Report;

class ReportDatagridConfigurationBuilder extends DatagridConfigurationBuilder
{
    /**
     * Constructor
     *
     * @param string                    $gridName
     * @param Report                    $report
     * @param FunctionProviderInterface $functionProvider
     * @param ManagerRegistry           $doctrine
     */
    public function __construct(
        $gridName,
        Report $report,
        FunctionProviderInterface $functionProvider,
        ManagerRegistry $doctrine
    ) {
        parent::__construct($gridName, $report, $functionProvider, $doctrine);

        $this->config->offsetSetByPath('[source][acl_resource]', 'oro_report_view');
        $this->config->offsetSetByPath(
            sprintf('%s[addExportAction]', ToolbarExtension::TOOLBAR_OPTION_PATH),
            true
        );
    }
}
