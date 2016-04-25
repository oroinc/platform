<?php

namespace Oro\Bundle\SegmentBundle\Event;

use Symfony\Component\EventDispatcher\Event;

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

    /**
     * @param array $widgetOptions
     */
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
