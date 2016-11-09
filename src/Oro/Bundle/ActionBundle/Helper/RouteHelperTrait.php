<?php

namespace Oro\Bundle\ActionBundle\Helper;

trait RouteHelperTrait
{
    /** @var string */
    protected $dialogRoute;

    /** @var string */
    protected $executionRoute;

    /** @var string */
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
     * @return $this
     */
    public function setDialogRoute($dialogRoute)
    {
        $this->dialogRoute = $dialogRoute;

        return $this;
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
     * @return $this
     */
    public function setExecutionRoute($executionRoute)
    {
        $this->executionRoute = $executionRoute;

        return $this;
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
     * @return $this
     */
    public function setWidgetRoute($widgetRoute)
    {
        $this->widgetRoute = $widgetRoute;

        return $this;
    }
}
