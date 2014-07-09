<?php

namespace Oro\Bundle\DataGridBundle\Extension\Pager;

use Oro\Bundle\DataGridBundle\Datagrid\Builder;
use Oro\Bundle\DataGridBundle\Datagrid\Common\MetadataObject;
use Oro\Bundle\DataGridBundle\Datagrid\Common\ResultsObject;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Datasource\DatasourceInterface;
use Oro\Bundle\DataGridBundle\Extension\AbstractExtension;
use Oro\Bundle\DataGridBundle\Extension\Pager\Orm\Pager;
use Oro\Bundle\DataGridBundle\Extension\Toolbar\ToolbarExtension;

/**
 * Class OrmPagerExtension
 * @package Oro\Bundle\DataGridBundle\Extension\Pager
 *
 * Responsibility of this extension is to apply pagination on query for ORM datasource
 */
class OrmPagerExtension extends AbstractExtension
{
    /** @var Pager */
    protected $pager;

    /**
     * @param Pager $pager
     */
    public function __construct(Pager $pager)
    {
        $this->pager = $pager;
    }

    /**
     * Prototype object
     */
    public function __clone()
    {
        $this->pager = clone $this->pager;
    }

    /**
     * {@inheritDoc}
     */
    public function isApplicable(DatagridConfiguration $config)
    {
        // enabled by default for ORM datasource
        $disabled = $this->getOr(PagerInterface::DISABLED_PARAM, false)
            || $config->offsetGetByPath(ToolbarExtension::TOOLBAR_PAGINATION_HIDE_OPTION_PATH, false);

        return !$disabled && $config->offsetGetByPath(Builder::DATASOURCE_TYPE_PATH) == OrmDatasource::TYPE;
    }

    /**
     * {@inheritDoc}
     */
    public function visitDatasource(DatagridConfiguration $config, DatasourceInterface $datasource)
    {
        $defaultPerPage = $config->offsetGetByPath(ToolbarExtension::PAGER_DEFAULT_PER_PAGE_OPTION_PATH, 10);

        $this->pager->setQueryBuilder($datasource->getQueryBuilder());
        $this->pager->setPage($this->getOr(PagerInterface::PAGE_PARAM, 1));
        $this->pager->setMaxPerPage($this->getOr(PagerInterface::PER_PAGE_PARAM, $defaultPerPage));
        $this->pager->init();
    }

    /**
     * {@inheritDoc}
     */
    public function visitResult(DatagridConfiguration $config, ResultsObject $result)
    {
        $result->offsetSetByPath(PagerInterface::TOTAL_PATH_PARAM, $this->pager->getNbResults());
    }

    /**
     * {@inheritDoc}
     */
    public function visitMetadata(DatagridConfiguration $config, MetadataObject $data)
    {
        $defaultPerPage = $config->offsetGetByPath(ToolbarExtension::PAGER_DEFAULT_PER_PAGE_OPTION_PATH, 10);

        $state = [
            'currentPage' => $this->getOr(PagerInterface::PAGE_PARAM, 1),
            'pageSize'    => $this->getOr(PagerInterface::PER_PAGE_PARAM, $defaultPerPage)
        ];

        $data->offsetAddToArray('state', $state);
    }

    /**
     * {@inheritDoc}
     */
    public function getPriority()
    {
        // Pager should proceed closest to end of accepting chain
        return -240;
    }

    /**
     * Get param or return specified default value
     *
     * @param string $paramName
     * @param mixed  $default
     *
     * @return mixed
     */
    protected function getOr($paramName, $default = null)
    {
        $pagerParameters = $this->getParameters()->get(PagerInterface::PAGER_ROOT_PARAM, []);

        return isset($pagerParameters[$paramName]) ? $pagerParameters[$paramName] : $default;
    }
}
