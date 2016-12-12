<?php

namespace Oro\Bundle\ActionBundle\Provider;

class RouteProvider implements RouteProviderInterface
{
    use RouteProviderTrait;

    /**
     * @param string $formDialogRoute
     * @param string $formPageRoute
     * @param string $executionRoute
     * @param string|null $widgetRoute
     */
    public function __construct($formDialogRoute, $formPageRoute, $executionRoute, $widgetRoute = null)
    {
        $this->formDialogRoute = $formDialogRoute;
        $this->formPageRoute = $formPageRoute;
        $this->executionRoute = $executionRoute;
        $this->widgetRoute = $widgetRoute;
    }
}
