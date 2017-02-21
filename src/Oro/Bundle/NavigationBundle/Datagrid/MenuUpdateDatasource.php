<?php

namespace Oro\Bundle\NavigationBundle\Datagrid;

use Knp\Menu\Util\MenuManipulator;

use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datasource\DatasourceInterface;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\NavigationBundle\Provider\BuilderChainProvider;
use Oro\Bundle\NavigationBundle\Provider\ConfigurationProvider;

class MenuUpdateDatasource implements DatasourceInterface
{
    /** @var BuilderChainProvider */
    protected $chainProvider;

    /** @var MenuManipulator */
    protected $menuManipulator;

    /** @var string */
    protected $scopeType;

    /** @var ConfigurationProvider */
    private $configurationProvider;

    /**
     * @param BuilderChainProvider  $chainProvider
     * @param MenuManipulator       $menuManipulator
     * @param ConfigurationProvider $configurationProvider
     * @param string                $scopeType
     */
    public function __construct(
        BuilderChainProvider $chainProvider,
        MenuManipulator $menuManipulator,
        ConfigurationProvider $configurationProvider,
        $scopeType
    ) {
        $this->chainProvider = $chainProvider;
        $this->menuManipulator = $menuManipulator;
        $this->configurationProvider = $configurationProvider;
        $this->scopeType = $scopeType;
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
        $menuConfiguration = $this->configurationProvider->getConfiguration(ConfigurationProvider::MENU_CONFIG_KEY);

        $rows = [];

        foreach ($menuConfiguration['tree'] as $name => $item) {
            $menuItem = $this->chainProvider->get($name);
            if ($menuItem->getExtra('scope_type') === $this->scopeType && !$menuItem->getExtra('read_only')) {
                $rows[] = new ResultRecord($this->menuManipulator->toArray($menuItem));
            }
        }

        return $rows;
    }
}
