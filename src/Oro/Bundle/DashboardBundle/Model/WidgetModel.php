<?php

namespace Oro\Bundle\DashboardBundle\Model;

use Oro\Bundle\DashboardBundle\Entity\Widget;
use Oro\Bundle\DashboardBundle\Entity\WidgetState;

/**
 * Represents dashboard widget.
 */
class WidgetModel implements EntityModelInterface
{
    /**
     * @var Widget
     */
    protected $entity;

    /**
     * @var array
     */
    protected $config;

    /**
     * @var WidgetState
     */
    protected $state;

    public function __construct(Widget $widget, array $config, WidgetState $widgetState)
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
     * @return int
     */
    public function getId()
    {
        return $this->getEntity()->getId();
    }

    /**
     * @param string $name
     * @return WidgetModel
     */
    public function setName($name)
    {
        $this->getEntity()->setName($name);
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
     * @return WidgetModel
     */
    public function setLayoutPosition(array $layoutPosition)
    {
        $this->getEntity()->setLayoutPosition($layoutPosition);

        return $this;
    }

    /**
     * @return array
     */
    public function getLayoutPosition()
    {
        return $this->getEntity()->getLayoutPosition();
    }

    /**
     * @param boolean $expanded
     * @return WidgetModel
     */
    public function setExpanded($expanded)
    {
        $this->getState()->setExpanded($expanded);

        return $this;
    }

    /**
     * @return bool
     */
    public function isExpanded()
    {
        return $this->getState()->isExpanded();
    }
}
