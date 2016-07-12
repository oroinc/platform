<?php

namespace Oro\Bundle\LayoutBundle\Provider;

use Oro\Bundle\RequireJSBundle\Config\Config as RequireJSConfig;
use Oro\Bundle\RequireJSBundle\Provider\AbstractConfigProvider;

use Oro\Component\Layout\Extension\Theme\Model\Theme;
use Oro\Component\Layout\Extension\Theme\Model\ThemeManager;

class RequireJSConfigProvider extends AbstractConfigProvider
{
    const REQUIREJS_CONFIG_CACHE_KEY    = 'layout_requirejs_config';
    const REQUIREJS_CONFIG_FILE         = 'require-config.js';
    const REQUIREJS_JS_DIR              = 'js/layout';

    /**
     * @var ThemeManager
     */
    protected $themeManager;

    /**
     * @var
     */
    protected $activeTheme;

    /**
     * @var string
     */
    protected $currentTheme;

    /**
     * @param ThemeManager $themeManager
     */
    public function setThemeManager(ThemeManager $themeManager)
    {
        $this->themeManager = $themeManager;
    }

    /**
     * @param string $themeName
     */
    public function setActiveTheme($themeName)
    {
        $this->activeTheme = $themeName;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfig()
    {
        $configs = $this->getConfigs();

        return $configs[$this->activeTheme];
    }

    /**
     * {@inheritdoc}
     */
    public function collectConfigs()
    {
        $baseConfig = $this->config;

        $configs = [];
        foreach ($this->getAllThemes() as $theme) {
            $this->currentTheme = $theme->getName();

            $this->config = $baseConfig;

            $configPath = implode(
                DIRECTORY_SEPARATOR,
                [self::REQUIREJS_JS_DIR, $this->currentTheme, self::REQUIREJS_CONFIG_FILE]
            );

            $buildPath = isset($this->config['config']['build_path']) ?
                $this->config['config']['build_path'] : $this->config['build_path'];

            $buildPath = implode(
                DIRECTORY_SEPARATOR,
                [self::REQUIREJS_JS_DIR, $this->currentTheme, $buildPath]
            );

            $configs[$this->currentTheme] = $this->createRequireJSConfig($configPath, $buildPath);
        }

        return $configs;
    }

    /**
     * {@inheritdoc}
     */
    protected function getFiles($bundle)
    {
        $theme = $this->getTheme($this->currentTheme);

        $files = [];

        if ($theme->getParentTheme()) {
            $this->currentTheme = $theme->getParentTheme();
            $files = array_merge($files, $this->getFiles($bundle));
        }

        $this->currentTheme = $theme->getName();

        $reflection = new \ReflectionClass($bundle);
        $file = dirname($reflection->getFileName()) .
            sprintf('/Resources/views/layouts/%s/requirejs.yml', $theme->getDirectory());

        if (is_file($file)) {
            $files[] = $file;
        }

        return $files;
    }

    /**
     * Get theme by theme name
     *
     * @param string $themeName
     *
     * @return Theme
     */
    protected function getTheme($themeName)
    {
        return $this->themeManager->getTheme($themeName);
    }

    /**
     * @return Theme[]
     */
    protected function getAllThemes()
    {
        return $this->themeManager->getAllThemes();
    }

    /**
     * {@inheritdoc}
     */
    protected function getCacheKey()
    {
        return self::REQUIREJS_CONFIG_CACHE_KEY;
    }
}
