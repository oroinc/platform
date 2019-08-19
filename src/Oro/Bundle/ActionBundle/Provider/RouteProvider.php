<?php

namespace Oro\Bundle\ActionBundle\Provider;

/**
 * The provider for action routes.
 */
class RouteProvider implements RouteProviderInterface
{
    /** @var string */
    private $formDialogRoute;

    /** @var string */
    private $formPageRoute;

    /** @var string */
    private $executionRoute;

    /** @var string|null */
    private $widgetRoute;

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

    /**
     * {@inheritdoc}
     */
    public function getWidgetRoute()
    {
        return $this->widgetRoute;
    }

    /**
     * {@inheritdoc}
     */
    public function getFormDialogRoute()
    {
        return $this->formDialogRoute;
    }

    /**
     * {@inheritdoc}
     */
    public function getFormPageRoute()
    {
        return $this->formPageRoute;
    }

    /**
     * {@inheritdoc}
     */
    public function getExecutionRoute()
    {
        return $this->executionRoute;
    }
}
