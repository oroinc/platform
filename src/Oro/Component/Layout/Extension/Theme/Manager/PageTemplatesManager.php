<?php

namespace Oro\Component\Layout\Extension\Theme\Manager;

use Oro\Component\Layout\Extension\Theme\Model\ThemeManager;

class PageTemplatesManager
{
    /** @var ThemeManager */
    private $themeManager;

    /**
     * @param ThemeManager $themeManager
     */
    public function __construct(ThemeManager $themeManager)
    {
        $this->themeManager = $themeManager;
    }

    /**
     * @return array
     */
    public function getRoutePageTemplates()
    {
        $routes = [];
        $themes = $this->themeManager->getAllThemes();

        foreach ($themes as $theme) {
            $titles =  $theme->getPageTemplateTitles();

            foreach ($theme->getPageTemplates() as $pageTemplate) {
                $routeName = $pageTemplate->getRouteName();
                $routeTitle = isset($titles[$routeName]) ? $titles[$routeName] : $routeName;
                $routes[$routeName]['label'] = $routeTitle;
                $routes[$routeName]['choices'][$pageTemplate->getLabel()] = $pageTemplate->getKey();
            }
        }

        return $routes;
    }
}
