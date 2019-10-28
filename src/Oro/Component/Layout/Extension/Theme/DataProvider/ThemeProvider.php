<?php

namespace Oro\Component\Layout\Extension\Theme\DataProvider;

use Oro\Component\Layout\Extension\Theme\Model\Theme;
use Oro\Component\Layout\Extension\Theme\Model\ThemeManager;

/**
 * Provides theme icon and path to css files in theme by passed styles entry point
 */
class ThemeProvider
{
    /** @var ThemeManager */
    protected $themeManager;

    /** @var Theme[] */
    protected $themes = [];

    /**
     * @param ThemeManager $themeManager
     */
    public function __construct(ThemeManager $themeManager)
    {
        $this->themeManager = $themeManager;
    }

    /**
     * @param string $themeName
     *
     * @return string
     */
    public function getIcon($themeName)
    {
        return $this->getTheme($themeName)->getIcon();
    }

    /**
     * @param string $themeName
     *
     * @return string
     */
    public function getLogo($themeName)
    {
        return $this->getTheme($themeName)->getLogo();
    }

    /**
     * @param string $themeName
     *
     * @return array
     */
    public function getImagePlaceholders($themeName): array
    {
        return $this->getTheme($themeName)->getImagePlaceholders();
    }

    /**
     * @param string $themeName
     * @param string $sectionName
     *
     * @return string|null
     */
    public function getStylesOutput($themeName, $sectionName = 'styles')
    {
        $assets = $this->getTheme($themeName)->getConfigByKey('assets');
        if ($assets && array_key_exists($sectionName, $assets) && array_key_exists('output', $assets[$sectionName])) {
            return sprintf('layout-build/%s/%s', $themeName, $assets[$sectionName]['output']);
        }

        $parentTheme = $this->getTheme($themeName)->getParentTheme();
        if ($parentTheme) {
            return $this->getStylesOutput($parentTheme, $sectionName);
        }

        return null;
    }

    /**
     * @param string $themeName
     *
     * @return Theme
     */
    private function getTheme($themeName)
    {
        if (!array_key_exists($themeName, $this->themes)) {
            $this->themes[$themeName] = $this->themeManager->getTheme($themeName);
        }

        return $this->themes[$themeName];
    }
}
