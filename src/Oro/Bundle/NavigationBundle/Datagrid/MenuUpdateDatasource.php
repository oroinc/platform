<?php

namespace Oro\Bundle\NavigationBundle\Datagrid;

use Knp\Menu\MenuItem;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datasource\DatasourceInterface;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\NavigationBundle\Provider\BuilderChainProvider;

/**
 * Class MenuUpdateDatasource
 * @package Oro\Bundle\NavigationBundle\Datagrid
 */
class MenuUpdateDatasource implements DatasourceInterface
{
    /** @var BuilderChainProvider */
    protected $chainProvider;

    /**
     * @var string
     */
    protected $area;

    /**
     * @param BuilderChainProvider $chainProvider
     * @param string               $area
     */
    public function __construct(BuilderChainProvider $chainProvider, $area)
    {
        $this->chainProvider = $chainProvider;
        $this->area = $area;
    }

    /**
     * {@inheritDoc}
     */
    public function process(DatagridInterface $grid, array $config)
    {
        $grid->setDatasource(clone $this);
    }

    /**
     * @return array
     */
    public function getResults()
    {
        $rows = [];
        $menuItems = $this->chainProvider->getMenuListByArea($this->area);

        /** @var MenuItem $menuItem */
        foreach ($menuItems as $menuItem) {
            $rows[] = new ResultRecord(['menu' => $menuItem->getName(), 'title' => $menuItem->getName()]);
        }

        return $rows;
    }
}
