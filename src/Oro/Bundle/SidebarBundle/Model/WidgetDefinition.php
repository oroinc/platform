<?php

namespace Oro\Bundle\SidebarBundle\Model;

class WidgetDefinition
{
    /**
     * @var string
     */
    protected $icon;

    /**
     * @var string
     */
    protected $module;

    /**
     * @var string
     */
    protected $title;

    /**
     * @var string
     */
    protected $placement;

    /**
     * @return string
     */
    public function getIcon()
    {
        return $this->icon;
    }

    /**
     * @param string $icon
     * @return WidgetDefinition
     */
    public function setIcon($icon)
    {
        $this->icon = $icon;

        return $this;
    }

    /**
     * @return string
     */
    public function getModule()
    {
        return $this->module;
    }

    /**
     * @param string $module
     * @return WidgetDefinition
     */
    public function setModule($module)
    {
        $this->module = $module;

        return $this;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param string $title
     * @return WidgetDefinition
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * @return string
     */
    public function getPlacement()
    {
        return $this->placement;
    }

    /**
     * @param string $placement
     * @return WidgetDefinition
     */
    public function setPlacement($placement)
    {
        $this->placement = $placement;

        return $this;
    }
}
