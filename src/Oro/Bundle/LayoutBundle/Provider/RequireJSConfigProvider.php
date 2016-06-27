<?php

namespace Oro\Bundle\LayoutBundle\Provider;

use Oro\Bundle\RequireJSBundle\Provider\Config;
use Oro\Component\Config\Loader\YamlCumulativeFileLoader;
use Oro\Component\Config\Loader\FolderingCumulativeFileLoader;
use Oro\Component\Layout\Extension\Theme\Model\Theme;
use Oro\Component\Layout\Extension\Theme\Model\ThemeManager;
use Oro\Component\PhpUtils\ArrayUtil;
use Symfony\Component\Yaml\Yaml;

class RequireJSConfigProvider extends Config
{
    const REQUIREJS_CONFIG_CACHE_KEY    = 'layout_requirejs_config';

    const REQUIREJS_CONFIG_FILE         = 'js/require-config1.js';

    /**
     * @var ThemeManager
     */
    protected $themeManager;

    /**
     * {@inheritdoc}
     */
    public function getConfigFilePath($config)
    {
        return $config['config']['config_key'] . DIRECTORY_SEPARATOR . self::REQUIREJS_CONFIG_FILE;
    }

    /**
     * {@inheritdoc}
     */
    public function getOutputFilePath($config)
    {
        $path = !empty($config['config']['build_path'])
            ? $config['config']['build_path']
            : parent::getOutputFilePath($config);

        return $config['config']['config_key'] . DIRECTORY_SEPARATOR . $path;
    }

    /**
     * {@inheritdoc}
     */
    public function collectAllConfigs()
    {
        $configs = [];
        foreach ($this->getAllThemes() as $theme) {
            $configs[$theme->getName()] = [
                'mainConfig'    => $this->generateMainConfig($theme->getName()),
                'buildConfig'   => $this->collectBuildConfig($theme->getName()),
            ];
        }

        return $configs;
    }

    /**
     * {@inheritdoc}
     */
    protected function collectBuildConfig($key = Config::REQUIREJS_DEFAULT_KEY)
    {
        $config = $this->collectConfigs($key);

        return $this->extractBuildConfig($config);
    }

    /**
     * {@inheritdoc}
     */
    protected function getFiles($bundle, $key)
    {
        $theme = $this->getTheme($key);
        $reflection = new \ReflectionClass($bundle);

        $files = [];
        if ($theme->getParentTheme()) {
            $files = array_merge($files, $this->getFiles($bundle, $theme->getParentTheme()));
        }

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
        return $this->getThemeManager()->getTheme($themeName);
    }

    /**
     * @return Theme[]
     */
    protected function getAllThemes()
    {
        return $this->getThemeManager()->getAllThemes();
    }

    /**
     * @return ThemeManager
     */
    protected function getThemeManager()
    {
        if (!$this->themeManager) {
            $this->themeManager = $this->container->get('oro_layout.theme_manager');
        }

        return $this->themeManager;
    }

    /**
     * {@inheritdoc}
     */
    protected function getCacheKey()
    {
        return self::REQUIREJS_CONFIG_CACHE_KEY;
    }
}
