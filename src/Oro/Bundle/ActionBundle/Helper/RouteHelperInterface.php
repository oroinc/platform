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
    public function getFormDialogRoute();

    /**
     * @return string
     */
    public function getFormPageRoute();

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
     * @param string $formDialogRoute
     *
     * @return RouteHelperInterface
     */
    public function setFormDialogRoute($formDialogRoute);

    /**
     * @param string $formPageRoute
     *
     * @return RouteHelperInterface
     */
    public function setFormPageRoute($formPageRoute);

    /**
     * @param string $widgetRoute
     *
     * @return RouteHelperInterface
     */
    public function setWidgetRoute($widgetRoute);
}
