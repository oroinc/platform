<?php

namespace Oro\Bundle\LayoutBundle\Layout\Loader;

class ThemeAndRoutePathProvider extends AbstractPathProvider
{
    /**
     * {@inheritdoc}
     */
    public function getPaths()
    {
        $filterPaths = [];

        $themeName = $this->context->getOr('theme');
        if ($themeName) {
            $routeName = $this->context->getOr('route_name');
            foreach ($this->getThemesHierarchy($themeName) as $theme) {
                $filterPaths[] = $theme->getDirectory();
                if ($routeName) {
                    $filterPaths[] = implode(self::DELIMITER, [$theme->getDirectory(), $routeName]);
                }
            }
        }

        return $filterPaths;
    }
}
