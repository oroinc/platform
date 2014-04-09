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

    /**
     * @param int $column
     * @param bool $appendGreater
     * @param bool $appendLesser
     * @return array
     */
    public function getOrderedColumnWidgets($column, $appendGreater = false, $appendLesser = false)
    {
        $elements = $this->widgets->filter(
            function ($element) use ($column, $appendGreater, $appendLesser) {
                /** @var WidgetModel $element */
                $actualColumn = current($element->getWidget()->getLayoutPosition());
                return
                    ($actualColumn == $column) ||
                    ($appendGreater && $actualColumn > $column) ||
                    ($appendLesser && $actualColumn < $column);
            }
        );

        $result = $elements->getValues();

        usort(
            $result,
            function ($first, $second) {
                /** @var WidgetModel $first */
                /** @var WidgetModel $second */
                $firstPosition = $first->getWidget()->getLayoutPosition();
                $secondPosition = $second->getWidget()->getLayoutPosition();
                return $firstPosition[1] - $secondPosition[1];
            }
        );

        return $result;
    }
}
