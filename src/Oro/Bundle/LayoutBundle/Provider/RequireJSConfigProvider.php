<?php

namespace Oro\Bundle\LayoutBundle\Provider;

use Symfony\Component\Yaml\Yaml;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;

use Oro\Component\PhpUtils\ArrayUtil;
use Oro\Bundle\RequireJSBundle\Provider\Config;
use Oro\Component\Layout\Extension\Theme\Model\Theme;
use Oro\Component\Config\Loader\YamlCumulativeFileLoader;
use Oro\Component\Layout\Extension\Theme\Model\ThemeManager;
use Oro\Component\Config\Loader\FolderingCumulativeFileLoader;

class RequireJSConfigProvider extends Config
{
    const REQUIREJS_CONFIG_CACHE_KEY    = 'layout_requirejs_config';
    const REQUIREJS_CONFIG_FILE         = 'require-config.js';
    const REQUIREJS_JS_DIR              = 'js/layout';

    /**
     * @var ThemeManager
     */
    protected $themeManager;

    /**
     * {@inheritdoc}
     */
    public function __construct(ContainerInterface $container, EngineInterface $templating, $template)
    {
        parent::__construct($container, $templating, $template);
    }

    /**
     * {@inheritdoc}
     */
    public function getConfigFilePath()
    {
        return implode(
            [self::REQUIREJS_JS_DIR, $this->configKey, self::REQUIREJS_CONFIG_FILE],
            DIRECTORY_SEPARATOR
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getOutputFilePath()
    {
        $config = $this->collectConfigs();
        $path = !empty($config['config']['build_path'])
            ? $config['config']['build_path']
            : parent::getOutputFilePath($config);

        return implode(
            [self::REQUIREJS_JS_DIR, $this->configKey, $path],
            DIRECTORY_SEPARATOR
        );
    }

    /**
     * {@inheritdoc}
     */
    public function collectAllConfigs()
    {
        $configKey = $this->configKey;

        $configs = [];
        foreach ($this->getAllThemes() as $theme) {
            $this->configKey = $theme->getName();
            $configs[$this->configKey] = [
                'mainConfig'    => $this->generateMainConfig(),
                'buildConfig'   => $this->collectBuildConfig(),
            ];
        }

        $this->configKey = $configKey;

        return $configs;
    }

    /**
     * {@inheritdoc}
     */
    protected function getFiles($bundle)
    {
        $theme = $this->getTheme($this->configKey);
        $reflection = new \ReflectionClass($bundle);

        $files = [];
        if ($theme->getParentTheme()) {
            $this->configKey = $theme->getParentTheme();
            $files = array_merge($files, $this->getFiles($bundle));
            $this->configKey = $theme->getName();
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
