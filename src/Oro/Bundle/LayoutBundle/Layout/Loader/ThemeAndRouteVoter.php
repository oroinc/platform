<?php

namespace Oro\Bundle\LayoutBundle\Layout\Loader;

use Oro\Component\Layout\ContextInterface;

use Oro\Bundle\LayoutBundle\Layout\Extension\ThemeExtension;
use Oro\Bundle\LayoutBundle\Layout\Extension\RouteContextConfigurator;

class ThemeAndRouteVoter extends AbstractPathVoter
{
    /**
     * {@inheritdoc}
     */
    protected function getFilterPath(ContextInterface $context)
    {
        $filterPaths = [];

        $themeName = $context->get(ThemeExtension::PARAM_THEME);
        $routeName = $context->getOr(RouteContextConfigurator::PARAM_ROUTE_NAME);

        foreach ($this->getThemesHierarchy($themeName) as $theme) {
            $filterPaths[] = [$theme->getDirectory()];
            if ($routeName) {
                $filterPaths[] = [$theme->getDirectory(), $routeName];
            }
        }

        return $filterPaths;
    }
}
