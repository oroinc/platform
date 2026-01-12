<?php

namespace Oro\Bundle\DashboardBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Dispatched when widget configuration is being loaded.
 *
 * This event allows listeners to modify or extend widget configuration before it is used
 * to render or process widgets. Event listeners can add, remove, or modify configuration
 * options to customize widget behavior dynamically based on runtime conditions.
 */
class WidgetConfigurationLoadEvent extends Event
{
    public const EVENT_NAME = 'oro_dashboard.widget_configuration_load';

    /**
     * @var array
     */
    protected $configuration = [];

    public function __construct(array $configuration = [])
    {
        $this->configuration = $configuration;
    }

    /**
     * @return array
     */
    public function getConfiguration()
    {
        return $this->configuration;
    }

    public function setConfiguration(array $configuration = [])
    {
        $this->configuration = $configuration;
    }
}
