<?php

namespace Oro\Bundle\DashboardBundle\Event;

use Symfony\Component\EventDispatcher\Event;

class WidgetOptionsLoadEvent extends Event
{
    const EVENT_NAME = 'oro_dashboard.widget_options_load';

    /** @var array */
    protected $options = [];

    /**
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        $this->options = $options;
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @param array $options
     */
    public function setOptions(array $options = [])
    {
        $this->options = $options;
    }
}
