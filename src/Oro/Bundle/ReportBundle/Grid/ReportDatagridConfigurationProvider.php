<?php

namespace Oro\Bundle\ReportBundle\Grid;

use Symfony\Bridge\Doctrine\ManagerRegistry;
use Oro\Bundle\DataGridBundle\Provider\ConfigurationProviderInterface;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\FunctionProviderInterface;
use Oro\Bundle\QueryDesignerBundle\Exception\InvalidConfigurationException;

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
     * @var DatagridConfiguration
     */
    private $configuration = null;

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
        if ($this->configuration === null) {
            $id      = intval(substr($gridName, strlen(self::GRID_PREFIX)));
            $repo    = $this->doctrine->getRepository('OroReportBundle:Report');
            $report  = $repo->find($id);
            $builder = new ReportDatagridConfigurationBuilder(
                $gridName,
                $report,
                $this->functionProvider,
                $this->doctrine
            );

            $this->configuration = $builder->getConfiguration();
        }

        return $this->configuration;
    }

    /**
     * Check whether a report is valid or not
     *
     * @param string $gridName
     * @return bool
     */
    public function isReportValid($gridName)
    {
        try {
            $this->getConfiguration($gridName);
        } catch (InvalidConfigurationException $invalidConfigEx) {
            return false;
        }

        return true;
    }
}
