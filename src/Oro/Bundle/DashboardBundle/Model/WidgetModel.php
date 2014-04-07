<?php

namespace Oro\Bundle\DashboardBundle\Model;

use Oro\Bundle\DashboardBundle\Entity\DashboardWidget;

class WidgetModel
{
    protected $config;

    /**
     * @var DashboardWidget
     */
    protected $widget;

    /**
     * @return array
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @return DashboardWidget
     */
    public function getWidget()
    {
        return $this->widget;
    }

    public function __construct(array $config, DashboardWidget $widget)
    {
        $this->config = $config;
        $this->widget = $widget;
    }
}
