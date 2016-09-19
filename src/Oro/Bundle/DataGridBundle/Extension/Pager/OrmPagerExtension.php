<?php

namespace Oro\Bundle\DataGridBundle\Extension\Pager;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Datasource\DatasourceInterface;
use Oro\Bundle\DataGridBundle\Extension\Mode\ModeExtension;
use Oro\Bundle\DataGridBundle\Extension\Toolbar\ToolbarExtension;

/**
 * Responsibility of this extension is to apply pagination on query for ORM datasource
 */
class OrmPagerExtension extends AbstractPagerExtension
{
    /**
     * {@inheritDoc}
     */
    public function isApplicable(DatagridConfiguration $config)
    {
        return
            $config->getDatasourceType() === OrmDatasource::TYPE
            && !$this->getOr(PagerInterface::DISABLED_PARAM, false)
            && !$config->offsetGetByPath(ToolbarExtension::TOOLBAR_PAGINATION_HIDE_OPTION_PATH, false);
    }

    /**
     * {@inheritDoc}
     */
    public function visitDatasource(DatagridConfiguration $config, DatasourceInterface $datasource)
    {
        $defaultPerPage = $config->offsetGetByPath(ToolbarExtension::PAGER_DEFAULT_PER_PAGE_OPTION_PATH, 10);

        /** @var $datasource OrmDatasource */
        if ($datasource->getCountQb()) {
            $this->pager->setCountQb($datasource->getCountQb());
        }
        $this->pager->setQueryBuilder($datasource->getQueryBuilder());
        $this->pager->setSkipAclCheck($config->isDatasourceSkipAclApply());
        $this->pager->setAclPermission($config->getDatasourceAclApplyPermission());
        $this->pager->setSkipCountWalker(
            $config->offsetGetByPath(DatagridConfiguration::DATASOURCE_SKIP_COUNT_WALKER_PATH)
        );

        if ($config->offsetGetByPath(ToolbarExtension::PAGER_ONE_PAGE_OPTION_PATH, false) ||
            $config->offsetGetByPath(ModeExtension::MODE_OPTION_PATH) === ModeExtension::MODE_CLIENT
        ) {
            // no restrictions applied
            $this->pager->setPage(0);
            $this->pager->setMaxPerPage(0);
        } else {
            $this->pager->setPage($this->getOr(PagerInterface::PAGE_PARAM, 1));
            $this->pager->setMaxPerPage($this->getOr(PagerInterface::PER_PAGE_PARAM, $defaultPerPage));
        }
        $this->tryAdjustTotalCount();
        $this->pager->init();
    }

    /**
     * If adjusted count will be provided in parameters this extension will pass it to pager
     * to prevent unneeded count query.
     */
    protected function tryAdjustTotalCount()
    {
        $adjustedCount = $this->getOr(PagerInterface::ADJUSTED_COUNT);

        if (null !== $adjustedCount && is_int($adjustedCount) && $adjustedCount >= 0) {
            $this->pager->adjustTotalCount($adjustedCount);
        }
    }
}
