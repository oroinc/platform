<?php

namespace Oro\Bundle\DataGridBundle\Event;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Entity\GridView;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class representing event data for `oro_datagrid.grid_views_load` event
 */
class GridViewsLoadEvent extends Event
{
    const EVENT_NAME = 'oro_datagrid.grid_views_load';

    /** @var string */
    protected $gridName;

    /** @var GridView[] */
    protected $gridViews = [];

    /** @var DatagridConfiguration */
    private $gridConfiguration;

    /**
     * @param string $gridName
     * @param GridView[] $gridViews
     */
    public function __construct($gridName, array $gridViews = [])
    {
        $this->gridName = $gridName;
        $this->gridViews = $gridViews;
    }

    /**
     * @return string
     */
    public function getGridName()
    {
        return $this->gridName;
    }

    /**
     * @return GridView[]
     */
    public function getGridViews()
    {
        return $this->gridViews;
    }

    /**
     * @param GridView[] $gridViews
     */
    public function setGridViews(array $gridViews = [])
    {
        $this->gridViews = $gridViews;
    }

    /**
     * @return DatagridConfiguration
     */
    public function getGridConfiguration(): DatagridConfiguration
    {
        return $this->gridConfiguration;
    }

    /**
     * @param DatagridConfiguration $gridConfiguration
     */
    public function setGridConfiguration(DatagridConfiguration $gridConfiguration): void
    {
        $this->gridConfiguration = $gridConfiguration;
    }
}
