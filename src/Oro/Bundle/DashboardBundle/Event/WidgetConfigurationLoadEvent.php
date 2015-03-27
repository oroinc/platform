<?php

namespace Oro\Bundle\DashboardBundle\Event;

use Symfony\Component\EventDispatcher\Event;

class WidgetConfigurationLoadEvent extends Event
{
    const EVENT_NAME = 'oro_dashboard.widget_configuration_load';

    /**
     * @var array
     */
    protected $configuration = [];

    /**
     * @param array $configuration
     */
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

    /**
     * @param array $configuration
     */
    public function setConfiguration(array $configuration = [])
    {
        $this->configuration = $configuration;
    }
}
