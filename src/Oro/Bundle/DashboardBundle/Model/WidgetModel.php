<?php

namespace Oro\Bundle\DashboardBundle\Model;

use Oro\Bundle\DashboardBundle\Entity\Widget;
use Oro\Bundle\DashboardBundle\Entity\WidgetState;

class WidgetModel
{
    /**
     * @var array
     */
    protected $config;

    /**
     * @var Widget
     */
    protected $entity;

    /**
     * @var WidgetState
     */
    protected $state;

    /**
     * @param Widget      $widget
     * @param array       $config
     * @param WidgetState $widgetState
     */
    public function __construct(Widget $widget, array $config, WidgetState $widgetState = null)
    {
        $this->entity = $widget;
        $this->config = $config;
        $this->state  = $widgetState;
    }

    /**
     * @return array
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @return Widget
     */
    public function getEntity()
    {
        return $this->entity;
    }

    /**
     * @return WidgetState
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * @param WidgetState $state
     */
    public function setState(WidgetState $state)
    {
        $this->state = $state;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->getEntity()->getId();
    }

    /**
     * @param string $name
     * @return Widget
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->getEntity()->getName();
    }

    /**
     * @param array $layoutPosition
     * @return Widget
     */
    public function setLayoutPosition(array $layoutPosition)
    {
        if ($state = $this->getState()) {
            $state->setLayoutPosition($layoutPosition);
        }

        return $this;
    }

    /**
     * @return array
     */
    public function getLayoutPosition()
    {
        return $this->getState() ?
            $this->getState()->getLayoutPosition() :
            $this->getEntity()->getLayoutPosition();
    }

    /**
     * @param boolean $expanded
     * @return Widget
     */
    public function setExpanded($expanded)
    {
        if ($state = $this->getState()) {
            $state->setExpanded($expanded);
        }

        return $this;
    }

    /**
     * @return bool
     */
    public function isExpanded()
    {
        return $this->getState() ?
            $this->getState()->isExpanded() :
            $this->getEntity()->isExpanded();
    }
}
