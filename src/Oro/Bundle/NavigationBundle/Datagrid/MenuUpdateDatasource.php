<?php

namespace Oro\Bundle\NavigationBundle\Datagrid;

use Knp\Menu\Util\MenuManipulator;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datasource\DatasourceInterface;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\NavigationBundle\Config\MenuConfiguration;
use Oro\Bundle\NavigationBundle\Provider\BuilderChainProvider;

class MenuUpdateDatasource implements DatasourceInterface
{
    /** @var BuilderChainProvider */
    protected $chainProvider;

    /** @var MenuManipulator */
    protected $menuManipulator;

    /** @var string */
    protected $scopeType;

    /** @var MenuConfiguration */
    protected $menuConfiguration;

    /**
     * @param BuilderChainProvider  $chainProvider
     * @param MenuManipulator       $menuManipulator
     * @param string                $scopeType
     * @param MenuConfiguration    $menuConfiguration
     */
    public function __construct(
        BuilderChainProvider $chainProvider,
        MenuManipulator $menuManipulator,
        $scopeType,
        MenuConfiguration $menuConfiguration
    ) {
        $this->chainProvider = $chainProvider;
        $this->menuManipulator = $menuManipulator;
        $this->scopeType = $scopeType;
        $this->menuConfiguration = $menuConfiguration;
    }

    /**
     * {@inheritDoc}
     */
    public function process(DatagridInterface $grid, array $config)
    {
        $datasource = clone $this;
        if (isset($config['scope_type'])) {
            $datasource->scopeType = $config['scope_type'];
        }
        $grid->setDatasource($datasource);
    }

    /**
     * @return array
     */
    public function getResults()
    {
        $rows = [];

        $tree = $this->menuConfiguration->getTree();
        foreach ($tree as $name => $item) {
            $menuItem = $this->chainProvider->get($name);
            if ($menuItem->getExtra('scope_type') === $this->scopeType && !$menuItem->getExtra('read_only')) {
                $rows[] = new ResultRecord($this->menuManipulator->toArray($menuItem));
            }
        }

        return $rows;
    }
}
