<?php

namespace Oro\Bundle\DashboardBundle\Model;

use Oro\Bundle\DashboardBundle\Entity\Widget;
use Oro\Bundle\DashboardBundle\Entity\WidgetState;
use JMS\Serializer\Annotation as JMS;

/**
 * @JMS\ExclusionPolicy("ALL")
 */
class WidgetModel implements EntityModelInterface
{
    /**
     * @var Widget
     */
    protected $entity;

    /**
     * @JMS\Expose
     *
     * @var array
     */
    protected $config;

    /**
     * @var WidgetState
     */
    protected $state;

    /**
     * @param Widget      $widget
     * @param array       $config
     * @param WidgetState $widgetState
     */
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
     * @JMS\VirtualProperty
     * @JMS\SerializedName("id")
     *
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
     * @JMS\VirtualProperty
     * @JMS\SerializedName("name")
     *
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
     * @JMS\VirtualProperty
     * @JMS\SerializedName("layout_position")
     *
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
     * @JMS\VirtualProperty
     * @JMS\SerializedName("expanded")
     *
     * @return bool
     */
    public function isExpanded()
    {
        return $this->getState()->isExpanded();
    }
}
