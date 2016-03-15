<?php

namespace Oro\Bundle\LayoutBundle\Provider;

use Oro\Component\Layout\Extension\Theme\Model\Theme;
use Oro\Component\Layout\Extension\Theme\Model\ThemeManager;

class ImageTypeConfigProvider
{
    /**
     * @var ThemeManager
     */
    protected $themeManager;
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
    public function getConfigs()
    {
        return $this->collectThemesConfig();
    }

    /**
     * @return array
     */
    protected function collectThemesConfig()
    {
        $configs = [];
        $themes = $this->themeManager->getAllThemes();
        foreach ($themes as $theme) {
            $configs = array_merge($configs, $this->getThemeConfig($theme));
        }

        return $configs;
    }

    /**
     * @param Theme $theme
     * @return array
     */
    protected function getThemeConfig(Theme $theme)
    {
        $imageTypeConfigs = $theme->getDataByKey('images', ['types' => []])['types'];

        return $imageTypeConfigs;
    }
}
