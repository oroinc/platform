<?php

namespace Oro\Bundle\ReportBundle\Grid;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Provider\ConfigurationProviderInterface;
use Oro\Bundle\QueryDesignerBundle\Exception\InvalidConfigurationException;
use Oro\Bundle\QueryDesignerBundle\Grid\BuilderAwareInterface;
use Oro\Bundle\QueryDesignerBundle\Grid\DatagridConfigurationBuilder;
use Oro\Bundle\ReportBundle\Entity\Report;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * The provider for configuration of datagrids used to show reports.
 */
class ReportDatagridConfigurationProvider implements ConfigurationProviderInterface, BuilderAwareInterface
{
    private ReportDatagridConfigurationBuilder $builder;
    private ManagerRegistry $doctrine;
    private CacheInterface $cache;
    private string $prefixCacheKey;

    public function __construct(
        ReportDatagridConfigurationBuilder $builder,
        ManagerRegistry $doctrine,
        CacheInterface $cache,
        string $prefixCacheKey
    ) {
        $this->builder = $builder;
        $this->doctrine = $doctrine;
        $this->cache = $cache;
        $this->prefixCacheKey = $prefixCacheKey;
    }

    public function isApplicable(string $gridName): bool
    {
        return $this->builder->isApplicable($gridName);
    }

    public function getConfiguration(string $gridName): DatagridConfiguration
    {
        return $this->cache->get($this->prefixCacheKey . '.' . $gridName, function () use ($gridName) {
            return $this->buildConfiguration($gridName);
        });
    }

    /**
     * Check whether a report is valid or not
     */
    public function isReportValid(string $gridName): bool
    {
        try {
            $this->getConfiguration($gridName);
        } catch (InvalidConfigurationException $e) {
            return false;
        }

        return true;
    }

    public function getBuilder(): DatagridConfigurationBuilder
    {
        return $this->builder;
    }

    private function buildConfiguration(string $gridName): DatagridConfiguration
    {
        $id = (int)(substr($gridName, \strlen(Report::GRID_PREFIX)));
        if (!$id) {
            throw new \RuntimeException(sprintf('The report ID not found in the "%s" grid name.', $gridName));
        }

        $report = $this->doctrine->getRepository(Report::class)->find($id);

        $this->builder->setGridName($gridName);
        $this->builder->setSource($report);

        return $this->builder->getConfiguration();
    }
}
