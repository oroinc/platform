<?php

namespace Oro\Bundle\DashboardBundle\Model;

use Oro\Bundle\DashboardBundle\Entity\Dashboard;
use Oro\Bundle\DashboardBundle\Entity\DashboardWidget;

class DashboardModel
{
    /**
     * @var WidgetsModelCollection
     */
    protected $widgets;

    /**
     * @var array
     */
    protected $config;

    /**
     * @var Dashboard
     */
    protected $dashboard;

    public function __construct(WidgetsModelCollection $widgets, array $config, Dashboard $dashboard)
    {
        $this->widgets = $widgets;
        $this->config = $config;
        $this->dashboard = $dashboard;
    }

    /**
     * @return array
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @return Dashboard
     */
    public function getDashboard()
    {
        return $this->dashboard;
    }

    /**
     * @return DashboardWidget[]
     */
    public function getWidgets()
    {
        return $this->widgets;
    }
}
