<?php

namespace Oro\Bundle\LayoutBundle\Layout\Loader;

use Oro\Component\Layout\ContextInterface;

class ThemeAndRouteVoter extends AbstractPathVoter
{
    /**
     * {@inheritdoc}
     */
    protected function getFilterPath(ContextInterface $context)
    {
        $filterPaths = [];

        $themeName = $context->getOr('theme');
        if ($themeName) {
            $routeName = $context->getOr('route_name');
            foreach ($this->getThemesHierarchy($themeName) as $theme) {
                $filterPaths[] = [$theme->getDirectory()];
                if ($routeName) {
                    $filterPaths[] = [$theme->getDirectory(), $routeName];
                }
            }
        }

        return $filterPaths;
    }
}
