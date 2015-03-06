<?php

namespace Oro\Bundle\UIBundle\Layout\Loader;

use Oro\Component\Layout\ContextInterface;

use Oro\Bundle\LayoutBundle\Layout\Loader\ThemeAndRouteVoter;
use Oro\Bundle\UIBundle\Layout\Extension\ActionContextConfigurator;
use Oro\Bundle\UIBundle\Layout\Extension\WidgetContextConfigurator;

class WidgetAndActionVoter extends ThemeAndRouteVoter
{
    /**
     * {@inheritdoc}
     */
    protected function getFilterPath(ContextInterface $context)
    {
        $filterPaths = [];

        $widgetName = $context->getOr(WidgetContextConfigurator::PARAM_WIDGET);
        $actionName = $context->getOr(ActionContextConfigurator::PARAM_ACTION);

        if ($widgetName || $actionName) {
            $basePaths = parent::getFilterPath($context);

            foreach ($basePaths as $path) {
                if ($actionName && count($path) === 1) {
                    // add action name to theme related path
                    $actionPath = array_merge($path, [$actionName]);
                    $filterPaths[] = $actionPath;

                    if ($widgetName) {
                        $filterPaths[] = array_merge($actionPath, [$widgetName]);
                    }
                }

                if ($widgetName) {
                    $filterPaths[] = array_merge($path, [$widgetName]);
                }
            }
        }

        return $filterPaths;
    }
}
