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
}
