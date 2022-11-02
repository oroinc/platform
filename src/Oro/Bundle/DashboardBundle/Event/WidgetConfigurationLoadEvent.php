<?php

namespace Oro\Bundle\DashboardBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

class WidgetConfigurationLoadEvent extends Event
{
    const EVENT_NAME = 'oro_dashboard.widget_configuration_load';

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
