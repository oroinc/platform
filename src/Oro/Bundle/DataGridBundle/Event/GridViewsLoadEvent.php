<?php

namespace Oro\Bundle\DataGridBundle\Event;

use Symfony\Component\EventDispatcher\Event;

use Oro\Bundle\DataGridBundle\Entity\GridView;

class GridViewsLoadEvent extends Event
{
    const EVENT_NAME = 'oro_datagrid.grid_views_load';

    /** @var string */
    protected $gridName;

    /** @var GridView[] */
    protected $gridViews = [];

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
}
