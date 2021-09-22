<?php

namespace Oro\Component\Layout\Extension\Theme\Manager;

use Oro\Component\Layout\Extension\Theme\Model\ThemeManager;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Responsibility of this class is to get page template config data for required themes.
 */
class PageTemplatesManager
{
    /** @var ThemeManager */
    private $themeManager;

    /** @var ThemeManager */
    private $translator;

    public function __construct(ThemeManager $themeManager, TranslatorInterface $translator)
    {
        $this->themeManager = $themeManager;
        $this->translator = $translator;
    }

    /**
     * @return array
     */
    public function getRoutePageTemplates()
    {
        $routes = [];
        $themes = $this->themeManager->getAllThemes();

        foreach ($themes as $theme) {
            $titles = $theme->getPageTemplateTitles();

            foreach ($theme->getPageTemplates() as $pageTemplate) {
                $routeName = $pageTemplate->getRouteName();

                $routes[$routeName]['label'] = $titles[$routeName] ?? $routeName;
                $routes[$routeName]['choices'][$pageTemplate->getKey()] = $pageTemplate->getLabel();
                $routes[$routeName]['descriptions'][$pageTemplate->getKey()]
                    = $this->translator->trans((string) $pageTemplate->getDescription());
            }
        }

        return $routes;
    }
}
