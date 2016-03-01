<?php

namespace Oro\Bundle\DashboardBundle\Event;

use Symfony\Component\EventDispatcher\Event;

use Oro\Bundle\DashboardBundle\Model\WidgetOptionBag;

class WidgetItemsLoadDataEvent extends Event
{
    const EVENT_NAME = 'oro_dashboard.widget_items_load_data';

    /** @var array */
    protected $items;

    /** @var array */
    protected $widgetConfig;

    /** @var WidgetOptionBag */
    protected $widgetOptions;

    /**
     * @param array           $items
     * @param array           $widgetConfig
     * @param WidgetOptionBag $widgetOptions
     */
    public function __construct(array $items, array $widgetConfig, WidgetOptionBag $widgetOptions)
    {
        $this->items         = $items;
        $this->widgetConfig  = $widgetConfig;
        $this->widgetOptions = $widgetOptions;
    }

    /**
     * @return array
     */
    public function getWidgetConfig()
    {
        return $this->widgetConfig;
    }

    /**
     * @return WidgetOptionBag
     */
    public function getWidgetOptions()
    {
        return $this->widgetOptions;
    }

    /**
     * @return array
     */
    public function getItems()
    {
        return $this->items;
    }

    /**
     * @param array $items
     */
    public function setItems(array $items = [])
    {
        $this->items = $items;
    }
}
