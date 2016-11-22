<?php

namespace Oro\Bundle\ActionBundle\Helper;

trait RouteHelperTrait
{
    /** @var string */
    protected $formDialogRoute;

    /** @var string */
    protected $formPageRoute;

    /** @var string */
    protected $executionRoute;

    /** @var string */
    protected $widgetRoute;

    /**
     * @return string
     */
    public function getFormDialogRoute()
    {
        return $this->formDialogRoute;
    }

    /**
     * @param string $formDialogRoute
     *
     * @return $this
     */
    public function setFormDialogRoute($formDialogRoute)
    {
        $this->formDialogRoute = $formDialogRoute;

        return $this;
    }

    /**
     * @return string
     */
    public function getFormPageRoute()
    {
        return $this->formPageRoute;
    }

    /**
     * @param string $formPageRoute
     *
     * @return $this
     */
    public function setFormPageRoute($formPageRoute)
    {
        $this->formPageRoute = $formPageRoute;

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
     *
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
     *
     * @return $this
     */
    public function setWidgetRoute($widgetRoute)
    {
        $this->widgetRoute = $widgetRoute;

        return $this;
    }
}
