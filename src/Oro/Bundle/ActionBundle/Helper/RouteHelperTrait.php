<?php

namespace Oro\Bundle\ActionBundle\Helper;

trait RouteHelperTrait
{
    /** @var  string */
    protected $dialogRoute;

    /** @var  string */
    protected $executionRoute;

    /** @var  string */
    protected $widgetRoute;

    /**
     * @return string
     */
    public function getDialogRoute()
    {
        return $this->dialogRoute;
    }

    /**
     * @param string $dialogRoute
     */
    public function setDialogRoute($dialogRoute)
    {
        $this->dialogRoute = $dialogRoute;
    }

    /**
     * @return string
     */
    public function getExecutionRoute()
    {
        return $this->executionRoute;
    }

    /**
     * @param string $executionRoute
     */
    public function setExecutionRoute($executionRoute)
    {
        $this->executionRoute = $executionRoute;
    }

    /**
     * @return string
     */
    public function getWidgetRoute()
    {
        return $this->widgetRoute;
    }

    /**
     * @param string $widgetRoute
     */
    public function setWidgetRoute($widgetRoute)
    {
        $this->widgetRoute = $widgetRoute;
    }
}
