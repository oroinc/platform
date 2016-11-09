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
     */
    public function setExecutionRoute($executionRoute);

    /**
     * @param string $dialogRoute
     */
    public function setDialogRoute($dialogRoute);

    /**
     * @param string $widgetRoute
     */
    public function setWidgetRoute($widgetRoute);
}
