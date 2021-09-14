<?php

namespace Oro\Bundle\ActionBundle\Provider;

/**
 * The provider for action routes.
 */
class RouteProvider implements RouteProviderInterface
{
    private string $formDialogRoute;

    private string $formPageRoute;

    private string $executionRoute;

    private string $widgetRoute;

    public function __construct(
        string $formDialogRoute,
        string $formPageRoute,
        string $executionRoute,
        string $widgetRoute = ''
    ) {
        $this->formDialogRoute = $formDialogRoute;
        $this->formPageRoute = $formPageRoute;
        $this->executionRoute = $executionRoute;
        $this->widgetRoute = $widgetRoute;
    }

    public function getWidgetRoute(): string
    {
        return $this->widgetRoute;
    }

    public function getFormDialogRoute(): string
    {
        return $this->formDialogRoute;
    }

    public function getFormPageRoute(): string
    {
        return $this->formPageRoute;
    }

    public function getExecutionRoute(): string
    {
        return $this->executionRoute;
    }
}
