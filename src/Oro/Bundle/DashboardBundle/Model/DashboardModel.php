<?php

namespace Oro\Bundle\DashboardBundle\Model;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\DashboardBundle\Entity\Dashboard;

class DashboardModel
{
    /**
     * @var Collection
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

    /**
     * @param Collection $widgets
     * @param array      $config
     * @param Dashboard  $dashboard
     */
    public function __construct(Collection $widgets, array $config, Dashboard $dashboard)
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
     * @return Collection
     */
    public function getWidgets()
    {
        return $this->widgets;
    }
}
