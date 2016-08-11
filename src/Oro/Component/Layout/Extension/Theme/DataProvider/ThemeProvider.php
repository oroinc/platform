<?php

namespace Oro\Component\Layout\Extension\Theme\DataProvider;

use Oro\Component\Layout\Extension\Theme\Model\Theme;
use Oro\Component\Layout\Extension\Theme\Model\ThemeManager;

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
     * @return string|null
     */
    public function getStylesOutput($themeName)
    {
        $assets = $this->getTheme($themeName)->getConfigByKey('assets');
        if ($assets && array_key_exists('styles', $assets)) {
            return array_key_exists('output', $assets['styles']) ? $assets['styles']['output'] : null;
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
