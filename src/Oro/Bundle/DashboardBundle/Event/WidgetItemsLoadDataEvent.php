<?php

namespace Oro\Bundle\DashboardBundle\Event;

use Oro\Bundle\DashboardBundle\Model\WidgetOptionBag;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Dispatched when widget items data is being loaded.
 *
 * This event allows listeners to filter, sort, or modify the items that will be displayed
 * in a widget. Listeners can access widget configuration and options to determine how to
 * process the items, enabling dynamic customization of widget content based on user preferences
 * or system configuration.
 */
class WidgetItemsLoadDataEvent extends Event
{
    const EVENT_NAME = 'oro_dashboard.widget_items_load_data';

    /** @var array */
    protected $items;

    /** @var array */
    protected $widgetConfig;

    /** @var WidgetOptionBag */
    protected $widgetOptions;

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

    public function setItems(array $items = [])
    {
        $this->items = $items;
    }
}
