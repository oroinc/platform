<?php

namespace Oro\Bundle\ReportBundle\Grid;

use Symfony\Bridge\Doctrine\ManagerRegistry;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Provider\ConfigurationProviderInterface;
use Oro\Bundle\QueryDesignerBundle\Exception\InvalidConfigurationException;
use Oro\Bundle\QueryDesignerBundle\Grid\BuilderAwareInterface;
use Oro\Bundle\ReportBundle\Entity\Report;

class ReportDatagridConfigurationProvider implements ConfigurationProviderInterface, BuilderAwareInterface
{
    /**
     * @var ManagerRegistry
     */
    protected $doctrine;

    /**
     * @var DatagridConfiguration
     */
    private $configuration = null;

    /**
     * @var ReportDatagridConfigurationBuilder
     */
    protected $builder;

    /**
     * @param ReportDatagridConfigurationBuilder $builder
     * @param ManagerRegistry                    $doctrine
     */
    public function __construct(
        ReportDatagridConfigurationBuilder $builder,
        ManagerRegistry $doctrine
    ) {
        $this->builder  = $builder;
        $this->doctrine = $doctrine;
    }

    /**
     * {@inheritdoc}
     */
    public function isApplicable($gridName)
    {
        return $this->builder->isApplicable($gridName);
    }

    /**
     * {@inheritdoc}
     */
    public function getConfiguration($gridName)
    {
        if ($this->configuration === null) {
            $id     = intval(substr($gridName, strlen(Report::GRID_PREFIX)));
            $repo   = $this->doctrine->getRepository('OroReportBundle:Report');
            $report = $repo->find($id);

            $this->builder->setGridName($gridName);
            $this->builder->setSource($report);

            $this->configuration = $this->builder->getConfiguration();
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

    /**
     * {@inheritdoc}
     */
    public function getBuilder()
    {
        return $this->builder;
    }
}
