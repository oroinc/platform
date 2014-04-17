<?php

namespace Oro\Bundle\DashboardBundle\Model;

use Oro\Bundle\DashboardBundle\Entity\Widget;

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
     * @param Widget $widget
     * @param array $config
     */
    public function __construct(Widget $widget, array $config)
    {
        $this->entity = $widget;
        $this->config = $config;
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
     * @return Widget
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
     * @return Widget
     */
    public function setExpanded($expanded)
    {
        $this->getEntity()->setExpanded($expanded);
        return $this;
    }

    /**
     * @return bool
     */
    public function isExpanded()
    {
        return $this->getEntity()->isExpanded();
    }
}
