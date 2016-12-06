<?php

namespace Oro\Bundle\ActionBundle\Provider;

trait RouteProviderTrait
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
     * @return string
     */
    public function getFormPageRoute()
    {
        return $this->formPageRoute;
    }

    /**
     * @return string
     */
    public function getExecutionRoute()
    {
        return $this->executionRoute;
    }

    /**
     * @return string
     */
    public function getWidgetRoute()
    {
        return $this->widgetRoute;
    }
}
