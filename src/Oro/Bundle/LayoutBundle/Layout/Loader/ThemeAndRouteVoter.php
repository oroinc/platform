<?php

namespace Oro\Bundle\LayoutBundle\Layout\Loader;

use Oro\Component\Layout\ContextInterface;

use Oro\Bundle\LayoutBundle\Theme\ThemeManager;
use Oro\Bundle\LayoutBundle\Layout\Extension\ThemeExtension;
use Oro\Bundle\LayoutBundle\Layout\Extension\RouteContextConfigurator;

class ThemeAndRouteVoter extends AbstractPathVoter
{
    /** @var ThemeManager */
    protected $manager;

    /**
     * @param ThemeManager $manager
     */
    public function __construct(ThemeManager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * {@inheritdoc}
     */
    protected function getFilterPath(ContextInterface $context)
    {
        $filterPaths = [];

        $themeName = $context->get(ThemeExtension::PARAM_THEME);
        $routeName = $context->getOr(RouteContextConfigurator::PARAM_ROUTE_NAME);

        while (null !== $themeName) {
            $theme = $this->manager->getTheme($themeName);

            $path = [$theme->getDirectory()];
            if ($routeName) {
                $path[] = $routeName;
            }

            $themeName     = $theme->getParentTheme();
            $filterPaths[] = $path;
        }

        return array_reverse($filterPaths);
    }
}
