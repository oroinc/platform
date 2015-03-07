<?php

namespace Oro\Bundle\UIBundle\Layout\Loader;

use Oro\Component\Layout\ContextInterface;

use Oro\Bundle\LayoutBundle\Layout\Loader\ThemeAndRouteVoter;

class WidgetAndActionVoter extends ThemeAndRouteVoter
{
    /**
     * {@inheritdoc}
     */
    protected function getFilterPath(ContextInterface $context)
    {
        $filterPaths = [];

        $widgetName = $context->getOr('widget_container');
        $actionName = $context->getOr('action');

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
