<?php

namespace Oro\Bundle\SegmentBundle\Event;

use Symfony\Component\EventDispatcher\Event;

class WidgetOptionsLoadEvent extends Event
{
    const EVENT_NAME = 'oro_segment.widget_options_load';

    /** @var array */
    protected $widgetOptions;

    /**
     * @param array $widgetOptions
     */
    public function __construct(array $widgetOptions)
    {
        $this->widgetOptions = $widgetOptions;
    }

    /**
     * @return array
     */
    public function getWidgetOptions()
    {
        return $this->widgetOptions;
    }

    /**
     * @param array $widgetOptions
     */
    public function setWidgetOptions(array $widgetOptions)
    {
        $this->widgetOptions = $widgetOptions;
    }
}
