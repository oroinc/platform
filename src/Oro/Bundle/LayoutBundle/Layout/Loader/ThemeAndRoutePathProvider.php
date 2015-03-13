<?php

namespace Oro\Bundle\LayoutBundle\Layout\Loader;

class ThemeAndRoutePathProvider extends AbstractPathProvider
{
    /**
     * {@inheritdoc}
     */
    public function getPaths()
    {
        $filterPaths = $themePaths = [];

        $themeName = $this->context->getOr('theme');
        if ($themeName) {
            $routeName = $this->context->getOr('route_name');
            foreach ($this->getThemesHierarchy($themeName) as $theme) {
                $themePaths[] = $filterPaths[] = $theme->getDirectory();
            }

            if ($routeName) {
                foreach ($themePaths as $path) {
                    $filterPaths[] = implode(self::DELIMITER, [$path, $routeName]);
                }
            }
        }

        return $filterPaths;
    }
}
