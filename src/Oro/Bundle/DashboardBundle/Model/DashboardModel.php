<?php

namespace Oro\Bundle\DashboardBundle\Model;

use Doctrine\Common\Collections\Collection;

use Oro\Bundle\DashboardBundle\Entity\Dashboard;

class DashboardModel
{
    const FIRST_COLUMN = 0;

    const DEFAULT_TEMPLATE = 'OroDashboardBundle:Index:default.html.twig';

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
    protected $entity;

    /**
     * @param Dashboard  $dashboard
     * @param Collection $widgets
     * @param array      $config
     */
    public function __construct(Dashboard $dashboard, Collection $widgets, array $config)
    {
        $this->widgets = $widgets;
        $this->config = $config;
        $this->entity = $dashboard;
    }

    /**
     * Get dashboard config
     *
     * @return array
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * Get dashboard entity
     *
     * @return Dashboard
     */
    public function getEntity()
    {
        return $this->entity;
    }

    /**
     * Get widgets models
     *
     * @return Collection
     */
    public function getWidgets()
    {
        return $this->widgets;
    }

    /**
     * Get identifier of dashboard
     *
     * @return int
     */
    public function getId()
    {
        return $this->getEntity()->getId();
    }

    /**
     * Add widget to dashboard
     *
     * @param WidgetModel $widget
     * @param bool $calculateLayoutPosition
     */
    public function addWidget($widget, $calculateLayoutPosition = false)
    {
        if ($calculateLayoutPosition) {
            $minPosition = $this->getMinLayoutPosition();
            $minPosition[1] = $minPosition[1] - 1;
            $widget->setLayoutPosition($minPosition);
        }
        $this->getEntity()->addWidget($widget->getEntity());
    }

    /**
     * Get min layout position
     *
     * @return array
     */
    protected function getMinLayoutPosition()
    {
        $result = array(self::FIRST_COLUMN, 0);

        /** @var WidgetModel $currentWidget */
        foreach ($this->getWidgets() as $currentWidget) {
            $position = $currentWidget->getLayoutPosition();

            if ($position[0] < $result[0]) {
                $result = $position;
            }

            if ($position[0] == $result[0] && $position[1] < $result[1]) {
                $result = $position;
            }
        }

        return $result;
    }

    /**
     * Get widget model by id
     *
     * @param integer $id
     * @return WidgetModel|null
     */
    public function getWidgetById($id)
    {
        /** @var WidgetModel $widget */
        foreach ($this->getWidgets() as $widget) {
            if ($widget->getId() == $id) {
                return $widget;
            }
        }
        return null;
    }

    /**
     * Get ordered widgets for column
     *
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
                $actualColumn = current($element->getLayoutPosition());
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
                $firstPosition = $first->getLayoutPosition();
                $secondPosition = $second->getLayoutPosition();
                return $firstPosition[1] - $secondPosition[1];
            }
        );

        return $result;
    }

    /**
     * Checks if dashboard has widget
     *
     * @param WidgetModel $widgetModel
     * @return bool
     */
    public function hasWidget(WidgetModel $widgetModel)
    {
        return $this->getEntity()->hasWidget($widgetModel->getEntity());
    }

    /**
     * Get dashboard label
     *
     * @return string
     */
    public function getLabel()
    {
        $label = $this->entity->getLabel();
        return $label ? $label : (isset($this->config['label']) ? $this->config['label'] : '');
    }

    /**
     * Get dashboard template
     *
     * @return string
     */
    public function getTemplate()
    {
        $config = $this->getConfig();
        return isset($config['twig']) ? $config['twig'] : self::DEFAULT_TEMPLATE;
    }
}
