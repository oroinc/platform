<?php

namespace Oro\Bundle\ActionBundle\Helper;

interface RouteHelperInterface
{
    /**
     * @return string
     */
    public function getWidgetRoute();

    /**
     * @return string
     */
    public function getDialogRoute();

    /**
     * @return string
     */
    public function getExecutionRoute();

    /**
     * @param string $executionRoute
     *
     * @return RouteHelperInterface
     */
    public function setExecutionRoute($executionRoute);

    /**
     * @param string $dialogRoute
     *
     * @return RouteHelperInterface
     */
    public function setDialogRoute($dialogRoute);

    /**
     * @param string $widgetRoute
     *
     * @return RouteHelperInterface
     */
    public function setWidgetRoute($widgetRoute);
}
