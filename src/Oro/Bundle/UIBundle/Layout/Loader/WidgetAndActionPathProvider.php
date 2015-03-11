<?php

namespace Oro\Bundle\UIBundle\Layout\Loader;

use Oro\Bundle\LayoutBundle\Layout\Loader\ThemeAndRoutePathProvider;

class WidgetAndActionPathProvider extends ThemeAndRoutePathProvider
{
    /**
     * {@inheritdoc}
     */
    public function getPaths()
    {
        $paths = [];

        $widgetName = $this->context->getOr('widget_container');
        $actionName = $this->context->getOr('action');

        if ($widgetName || $actionName) {
            $basePaths = parent::getPaths();

            foreach ($basePaths as $path) {
                if ($actionName && count($path) === 1) {
                    // add action name to theme related path
                    $actionPath = implode(self::DELIMITER, [$path, $actionName]);
                    $paths[]    = $actionPath;

                    if ($widgetName) {
                        $paths[] = implode(self::DELIMITER, [$actionPath, $widgetName]);
                    }
                }

                if ($widgetName) {
                    $paths[] = implode(self::DELIMITER, [$path, $widgetName]);
                }
            }
        }

        return $paths;
    }
}
