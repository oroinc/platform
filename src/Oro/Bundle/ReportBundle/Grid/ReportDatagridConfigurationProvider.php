<?php

namespace Oro\Bundle\ReportBundle\Grid;

use Symfony\Bridge\Doctrine\ManagerRegistry;
use Oro\Bundle\DataGridBundle\Provider\ConfigurationProviderInterface;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\FunctionProviderInterface;

class ReportDatagridConfigurationProvider implements ConfigurationProviderInterface
{
    const GRID_PREFIX = 'oro_report_table_';

    /**
     * @var FunctionProviderInterface
     */
    protected $functionProvider;

    /**
     * @var ManagerRegistry
     */
    protected $doctrine;

    /**
     * Constructor
     *
     * @param FunctionProviderInterface $functionProvider
     * @param ManagerRegistry           $doctrine
     */
    public function __construct(FunctionProviderInterface $functionProvider, ManagerRegistry $doctrine)
    {
        $this->functionProvider = $functionProvider;
        $this->doctrine         = $doctrine;
    }

    /**
     * {@inheritdoc}
     */
    public function isApplicable($gridName)
    {
        return (strpos($gridName, self::GRID_PREFIX) === 0);
    }

    /**
     * {@inheritdoc}
     */
    public function getConfiguration($gridName)
    {
        $id      = intval(substr($gridName, strlen(self::GRID_PREFIX)));
        $repo    = $this->doctrine->getRepository('OroReportBundle:Report');
        $report  = $repo->find($id);
        $builder = new ReportDatagridConfigurationBuilder(
            $gridName,
            $report,
            $this->functionProvider,
            $this->doctrine
        );

        return $builder->getConfiguration();
    }
}
