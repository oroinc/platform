<?php

namespace Oro\Bundle\NavigationBundle\Datagrid;

use Knp\Menu\Util\MenuManipulator;

use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datasource\DatasourceInterface;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\NavigationBundle\Provider\BuilderChainProvider;

class MenuUpdateDatasource implements DatasourceInterface
{
    /** @var BuilderChainProvider */
    protected $chainProvider;

    /** @var MenuManipulator */
    protected $menuManipulator;

    /** @var string */
    protected $area;

    /** @var array */
    protected $menuConfiguration;

    /**
     * @param BuilderChainProvider $chainProvider
     * @param MenuManipulator $menuManipulator
     * @param string $area
     */
    public function __construct(BuilderChainProvider $chainProvider, MenuManipulator $menuManipulator, $area)
    {
        $this->chainProvider = $chainProvider;
        $this->menuManipulator = $menuManipulator;
        $this->area = $area;
    }

    /**
     * @param array $configuration
     *
     * @return MenuUpdateDatasource
     */
    public function setMenuConfiguration(array $configuration)
    {
        $this->menuConfiguration = $configuration;

        return $this;
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

        foreach ($this->menuConfiguration['tree'] as $name => $item) {
            $menuItem = $this->chainProvider->get($name);
            if ($menuItem->getExtra('area') === $this->area && !$menuItem->getExtra('read_only')) {
                $rows[] = new ResultRecord($this->menuManipulator->toArray($menuItem));
            }
        }

        return $rows;
    }
}
