<?php

namespace Oro\Bundle\SearchBundle\Extension\Pager;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datasource\DatasourceInterface;
use Oro\Bundle\DataGridBundle\Extension\Pager\PagerInterface;
use Oro\Bundle\DataGridBundle\Extension\Pager\OrmPagerExtension;
use Oro\Bundle\DataGridBundle\Extension\Toolbar\ToolbarExtension;
use Oro\Bundle\SearchBundle\Extension\SearchDatasource;

class SearchPagerExtension extends OrmPagerExtension
{
    /** @var IndexerPager */
    protected $pager;

    /**
     * @param IndexerPager $pager
     */
    public function __construct(IndexerPager $pager)
    {
        $this->pager = $pager;
    }

    /**
     * {@inheritDoc}
     */
    public function isApplicable(DatagridConfiguration $config)
    {
        return $config->getDatasourceType() === SearchDatasource::TYPE;
    }

    /**
     * {@inheritDoc}
     */
    public function visitDatasource(DatagridConfiguration $config, DatasourceInterface $datasource)
    {
        $defaultPerPage = $config->offsetGetByPath(ToolbarExtension::PAGER_DEFAULT_PER_PAGE_OPTION_PATH, 10);

        $this->pager->setQuery($datasource->getQuery());
        $this->pager->setPage($this->getOr(PagerInterface::PAGE_PARAM, 1));
        $this->pager->setMaxPerPage($this->getOr(PagerInterface::PER_PAGE_PARAM, $defaultPerPage));
        $this->pager->init();
    }
}
