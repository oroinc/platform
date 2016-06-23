<?php

namespace Oro\Bundle\RequireJSBundle\Provider;

use Doctrine\Common\Cache\CacheProvider;

use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Templating\TemplateReferenceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;

use Oro\Component\PhpUtils\ArrayUtil;

class Config implements ConfigProviderInterface
{
    const REQUIREJS_CONFIG_CACHE_KEY    = 'requirejs_config';

    const MAIN_CONFIG_FILE_NAME         = 'js/require-config.js';
    const OUTPUT_FILE_NAME              = 'js/oro.min.js';

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var EngineInterface
     */
    protected $templating;

    /**
     * @var string|TemplateReferenceInterface
     */
    protected $template;

    /**
     * @var array
     */
    protected $collectedConfig;

    /**
     * Cache instance
     *
     * @var CacheProvider
     */
    protected $cache;

    /**
     * Active theme
     *
     * @var string
     */
    protected $theme = '_main';

    /**
     * @param ContainerInterface $container
     * @param EngineInterface $templating
     * @param string|TemplateReferenceInterface $template
     */
    public function __construct(ContainerInterface $container, EngineInterface $templating, $template)
    {
        $this->container = $container;
        $this->templating = $templating;
        $this->template = $template;
    }

    /**
     * Set cache instance
     *
     * @param CacheProvider $cache
     *
     * @return Config
     */
    public function setCache(CacheProvider $cache)
    {
        $this->cache = $cache;

        return $this;
    }

    /**
     * Set active theme
     *
     * @param string $theme
     *
     * @return Config
     */
    public function setTheme($theme)
    {
        $this->theme = $theme;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfigFilePath()
    {
        return self::MAIN_CONFIG_FILE_NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getOutputFilePath()
    {
        $configs = $this->collectConfigs();
        return $configs['build_path'];
    }

    /**
     * {@inheritdoc}
     */
    public function getMainConfig()
    {
        if ($this->cache) {
            if (!$this->cache->contains($this->getCacheKey())) {
                $this->generateBuildConfigs();
            }

            $configs = $this->cache->fetch($this->getCacheKey());
            if (!empty($configs[$this->theme])) {
                return $configs[$this->theme]['mainConfig'];
            }
        }

        return $this->generateMainConfig();
    }

    /**
     * {@inheritdoc}
     */
    public function generateBuildConfigs()
    {
        $buildConfig = [
            $this->theme => [
                'mainConfig' => $this->generateMainConfig(),
                'buildConfig' => $this->generateBuildConfig(),
            ]
        ];

        if (!$this->cache) {
            return $buildConfig;
        }

        $this->cache->save($this->getCacheKey(), $buildConfig);

        return $this->cache->fetch($this->getCacheKey());
    }

    /**
     * Generates main config for require.js
     *
     * @return string
     */
    public function generateMainConfig()
    {
        $requirejs = $this->collectConfigs();
        $config = $requirejs['config'];
        if (!empty($config['paths']) && is_array($config['paths'])) {
            foreach ($config['paths'] as &$path) {
                if (substr($path, 0, 8) === 'bundles/') {
                    $path = substr($path, 8);
                }
                if (substr($path, -3) === '.js') {
                    $path = substr($path, 0, -3);
                }
            }
        }
        return $this->templating->render($this->template, array('config' => $config));
    }

    /**
     * Generates build config for require.js
     *
     * @param string $configPath path to require.js main config
     *
     * @return array
     */
    public function generateBuildConfig($configPath = null)
    {
        $configPath = $configPath ? $configPath : $this->getConfigFilePath();

        $config = $this->collectConfigs();

        $config['build']['baseUrl'] = './bundles';
        $config['build']['out'] = './' . $this->getOutputFilePath();
        $config['build']['mainConfigFile'] = './' . $configPath;

        $paths = [
            // build-in configuration
            'require-config' => '../' . substr($configPath, 0, -3),
            // build-in require.js lib
            'require-lib' => 'ororequirejs/lib/require',
        ];

        $config['build']['paths'] = array_merge($config['build']['paths'], $paths);

        $config['build']['include'] = [];
        if (isset($config['config']['paths'])) {
            $config['build']['include'] = array_merge(
                array_keys($paths),
                array_keys($config['config']['paths'])
            );
        }

        return $config['build'];
    }

    /**
     * Goes across bundles and collects configurations
     *
     * @return array
     */
    public function collectConfigs()
    {
        if (!$this->collectedConfig) {
            $config = $this->container->getParameter('oro_require_js');
            $bundles = $this->container->getParameter('kernel.bundles');
            foreach ($bundles as $bundle) {
                if (is_file($file = $this->getFilePath($bundle))) {
                    $requirejs = Yaml::parse(file_get_contents(realpath($file)));
                    $config = ArrayUtil::arrayMergeRecursiveDistinct($config, $requirejs);
                }
            }

            $this->collectedConfig = $config;
        }

        return $this->collectedConfig;
    }

    /**
     * @param $bundle
     *
     * @return string
     */
    protected function getFilePath($bundle)
    {
        $reflection = new \ReflectionClass($bundle);
        return dirname($reflection->getFileName()) . '/Resources/config/requirejs.yml';
    }

    /**
     * @return string
     */
    protected function getCacheKey()
    {
        return self::REQUIREJS_CONFIG_CACHE_KEY;
    }
}
