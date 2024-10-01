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

    #[\Override]
    public function getWidgetRoute(): string
    {
        return $this->widgetRoute;
    }

    #[\Override]
    public function getFormDialogRoute(): string
    {
        return $this->formDialogRoute;
    }

    #[\Override]
    public function getFormPageRoute(): string
    {
        return $this->formPageRoute;
    }

    #[\Override]
    public function getExecutionRoute(): string
    {
        return $this->executionRoute;
    }
}
