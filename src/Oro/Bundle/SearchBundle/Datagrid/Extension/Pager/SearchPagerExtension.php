<?php

namespace Oro\Bundle\SearchBundle\Datagrid\Extension\Pager;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datasource\DatasourceInterface;
use Oro\Bundle\DataGridBundle\Extension\Pager\AbstractPagerExtension;
use Oro\Bundle\DataGridBundle\Extension\Pager\OrmPagerExtension;
use Oro\Bundle\DataGridBundle\Extension\Pager\PagerInterface;
use Oro\Bundle\DataGridBundle\Extension\Toolbar\ToolbarExtension;
use Oro\Bundle\SearchBundle\Datagrid\Datasource\SearchDatasource;

/**
 * Responsibility of this extension is to apply pagination on query for Search datasource
 */
class SearchPagerExtension extends OrmPagerExtension
{
    /** @var IndexerPager */
    protected $pager;

    public function __construct(IndexerPager $pager)
    {
        $this->pager = $pager;
    }

    /**
     * {@inheritDoc}
     */
    public function isApplicable(DatagridConfiguration $config)
    {
        return
            AbstractPagerExtension::isApplicable($config)
            && SearchDatasource::TYPE === $config->getDatasourceType();
    }

    /**
     * {@inheritDoc}
     */
    public function visitDatasource(DatagridConfiguration $config, DatasourceInterface $datasource)
    {
        $defaultPerPage = $config->offsetGetByPath(ToolbarExtension::PAGER_DEFAULT_PER_PAGE_OPTION_PATH, 10);
        $onePage = $config->offsetGetByPath(ToolbarExtension::PAGER_ONE_PAGE_OPTION_PATH, false);

        /** @var $datasource SearchDatasource */
        $this->pager->setQuery($datasource->getSearchQuery());
        $this->pager->setPage($this->getOr(PagerInterface::PAGE_PARAM, 1));

        if ($onePage) {
            $this->pager->setMaxPerPage(self::SOFT_LIMIT);
        } else {
            $this->pager->setMaxPerPage($this->getOr(PagerInterface::PER_PAGE_PARAM, $defaultPerPage));
        }

        $this->pager->init();
    }
}
