<?php

namespace Oro\Bundle\ReportBundle\Grid;

use Doctrine\Common\Cache\Cache;

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
     * @var ReportDatagridConfigurationBuilder
     */
    protected $builder;

    /**
     * @var string
     */
    private $prefixCacheKey;

    /**
     * @var Cache
     */
    private $reportCacheManager;

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
     * @deprecated will be removed in version 2.3
     *
     * @param string $prefixCacheKey
     */
    public function setPrefixCacheKey($prefixCacheKey)
    {
        $this->prefixCacheKey = $prefixCacheKey;
    }

    /**
     * @deprecated will be removed in version 2.3
     *
     * @param Cache $reportCacheManager
     */
    public function setReportCacheManager(Cache $reportCacheManager)
    {
        $this->reportCacheManager = $reportCacheManager;
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
        $cacheKey = $this->getCacheKey($gridName);

        if ($this->reportCacheManager->contains($cacheKey)) {
            $config = $this->reportCacheManager->fetch($cacheKey);
        } else {
            $config = $this->prepareConfiguration($gridName);
            $this->reportCacheManager->save($cacheKey, $config);
        }

        return $config;
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

    /**
     * Builds configuration of report grid by grid name
     *
     * @param $gridName
     *
     * @return DatagridConfiguration
     */
    protected function prepareConfiguration($gridName)
    {
        $id     = intval(substr($gridName, strlen(Report::GRID_PREFIX)));
        $repo   = $this->doctrine->getRepository('OroReportBundle:Report');
        $report = $repo->find($id);

        $this->builder->setGridName($gridName);
        $this->builder->setSource($report);

        return $this->builder->getConfiguration();
    }

    /**
     * Return unique cache key for report by grid name
     *
     * @param $gridName
     *
     * @return string
     */
    private function getCacheKey($gridName)
    {
        return $this->prefixCacheKey.'.'.$gridName;
    }
}
