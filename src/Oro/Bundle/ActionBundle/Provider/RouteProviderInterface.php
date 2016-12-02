<?php

namespace Oro\Bundle\ActionBundle\Provider;

interface RouteProviderInterface
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
}
