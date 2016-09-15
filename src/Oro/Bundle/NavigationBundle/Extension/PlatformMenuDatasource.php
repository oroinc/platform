<?php

namespace Oro\Bundle\NavigationBundle\Extension;

use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datasource\DatasourceInterface;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\NavigationBundle\Provider\BuilderChainProvider;

class PlatformMenuDatasource implements DatasourceInterface
{
    const AREA = 'default';

    /** @var BuilderChainProvider */
    protected $chainProvider;

    /**
     * @param BuilderChainProvider $chainProvider
     */
    public function __construct(BuilderChainProvider $chainProvider)
    {
        $this->chainProvider = $chainProvider;
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

        if ($menus = $this->chainProvider->getMenusForArea(self::AREA)) {
            foreach ($menus as $key => $value) {
                $rows[] = new ResultRecord(['id' => $key, 'title' => $value]);
            }
        }

        return $rows;
    }
}
