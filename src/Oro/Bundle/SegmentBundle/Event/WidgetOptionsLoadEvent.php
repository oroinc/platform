<?php

namespace Oro\Bundle\SegmentBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event dispatched when loading widget options for segment display.
 *
 * This event allows listeners to customize the options for segment widgets displayed
 * in the application interface. Listeners can modify widget behavior, appearance, or
 * functionality based on the widget type. The event carries both the widget options
 * array and an optional widget type identifier for type-specific customization.
 */
class WidgetOptionsLoadEvent extends Event
{
    const EVENT_NAME = 'oro_segment.widget_options_load';

    /** @var array */
    protected $widgetOptions;

    /** @var string|null */
    protected $type;

    /**
     * @param array $widgetOptions
     * @param string|null $type
     */
    public function __construct(array $widgetOptions, $type = null)
    {
        $this->widgetOptions = $widgetOptions;
        $this->type = $type;
    }

    /**
     * @return array
     */
    public function getWidgetOptions()
    {
        return $this->widgetOptions;
    }

    public function setWidgetOptions(array $widgetOptions)
    {
        $this->widgetOptions = $widgetOptions;
    }

    /**
     * @return string|null
     */
    public function getWidgetType()
    {
        return $this->type;
    }
}
