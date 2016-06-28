<?php

namespace Oro\Bundle\RequireJSBundle\Provider;

use Doctrine\Common\Cache\CacheProvider;
use Doctrine\Common\Collections\ArrayCollection;

use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Templating\TemplateReferenceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;

use Oro\Component\PhpUtils\ArrayUtil;

class Config implements ConfigProviderInterface
{
    const REQUIREJS_CONFIG_CACHE_KEY    = 'requirejs_config';
    const REQUIREJS_CONFIG_FILE         = 'js/require-config.js';

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
     * Cache instance
     *
     * @var CacheProvider
     */
    protected $cache;

    /**
     * @var ArrayCollection
     */
    protected $configs;

    /**
     * @var string
     */
    protected $configKey = '_main';

    /**
     * @param ContainerInterface                $container
     * @param EngineInterface                   $templating
     * @param string|TemplateReferenceInterface $template
     */
    public function __construct(ContainerInterface $container, EngineInterface $templating, $template)
    {
        $this->container = $container;
        $this->templating = $templating;
        $this->template = $template;

        $this->configs = new ArrayCollection();
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
     * {@inheritdoc}
     */
    public function getOutputFilePath()
    {
        $config = $this->collectConfigs();
        return $config['build_path'];
    }

    /**
     * {@inheritdoc}
     */
    public function getConfigFilePath()
    {
        return self::REQUIREJS_CONFIG_FILE;
    }

    /**
     * {@inheritdoc}
     */
    public function getMainConfig($configKey = null)
    {
        $this->configKey = $configKey ? $configKey : $this->configKey;

        if (!$this->cache) {
            $configs = $this->collectAllConfigs();
        } else {
            if (!$this->cache->contains($this->getCacheKey())) {
                $this->cache->save($this->getCacheKey(), $this->collectAllConfigs());
            }

            $configs = $this->cache->fetch($this->getCacheKey());
        }

        return isset($configs[$this->configKey]) ? $configs[$this->configKey]['mainConfig'] : null;
    }

    /**
     * {@inheritdoc}
     */
    public function collectAllConfigs()
    {
        $configs = [
            $this->configKey => [
                'mainConfig'    => $this->generateMainConfig(),
                'buildConfig'   => $this->collectBuildConfig(),
            ]
        ];

        return $configs;
    }

    /**
     * Collect require.js main config
     *
     * @return string
     */
    public function generateMainConfig()
    {
        $config = $this->collectConfigs();

        return $this->extractMainConfig($config);
    }

    /**
     * Extract main config from config array
     *
     * @param array $config
     *
     * @return string
     */
    protected function extractMainConfig($config)
    {
        $config = $config['config'];
        if (!empty($config['paths']) && is_array($config['paths'])) {
            foreach ($config['paths'] as $key => $path) {
                if (substr($path, 0, 8) === 'bundles/') {
                    $path = substr($path, 8);
                }

                if (substr($path, -3) === '.js') {
                    $path = substr($path, 0, -3);
                }

                $config['paths'][$key] = $path;
            }
        }
        return $this->templating->render($this->template, ['config' => $config]);
    }

    /**
     * Collect require.js build config
     *
     * @return array
     */
    protected function collectBuildConfig()
    {
        $config = $this->collectConfigs();
        return $this->extractBuildConfig($config);
    }

    /**
     * Extract build config from config array
     *
     * @param array $config
     *
     * @return array
     */
    protected function extractBuildConfig($config)
    {
        $webRoot = $this->container->getParameter('oro_require_js.web_root') . DIRECTORY_SEPARATOR;

        $buildConfig = $config['build'];

        $paths = [
            'require-config'    => $webRoot . substr($this->getConfigFilePath(), 0, -3),
            'require-lib'       => 'ororequirejs/lib/require',
        ];

        $buildConfig = array_merge(
            $buildConfig,
            [
                'baseUrl'           => $webRoot . 'bundles',
                'out'               => $webRoot . $this->getOutputFilePath(),
                'mainConfigFile'    => $webRoot . $this->getConfigFilePath(),
                'include'           => [],
                'paths'             => array_merge($config['build']['paths'], $paths)
            ]
        );

        if (isset($config['config']['paths'])) {
            $buildConfig['include'] = array_merge(
                array_keys($paths),
                array_keys($config['config']['paths'])
            );
        }

        return $buildConfig;
    }

    /**
     * Collect all require.js config
     *
     * @return array
     */
    public function collectConfigs()
    {
        if (!$this->configs->containsKey($this->configKey)) {
            $this->configs->set($this->configKey, $this->collectBundlesConfig());
        }

        return $this->configs->get($this->configKey);
    }

    /**
     * Collect require.js config from all bundles
     *
     * @return array
     */
    protected function collectBundlesConfig()
    {
        $config = $this->container->getParameter('oro_require_js');

        $bundles = $this->container->getParameter('kernel.bundles');
        foreach ($bundles as $bundle) {
            foreach ($this->getFiles($bundle) as $file) {
                $extendedConfig = Yaml::parse(file_get_contents(realpath($file)));
                $config = ArrayUtil::arrayMergeRecursiveDistinct($config, $extendedConfig);
            }
        }

        return $config;
    }

    /**
     * Get config files from bundle
     *
     * @param string $bundle
     *
     * @return array
     */
    protected function getFiles($bundle)
    {
        $reflection = new \ReflectionClass($bundle);
        $file = dirname($reflection->getFileName()) . '/Resources/config/requirejs.yml';

        return is_file($file) ? [$file] : [];
    }

    /**
     * Generates build config for require.js
     *
     * @param string $configPath path to require.js main config
     *
     * @return array
     *
     * @deprecated
     */
    public function generateBuildConfig($configPath = null)
    {
        return $this->collectBuildConfig();
    }

    /**
     * @return string
     */
    protected function getCacheKey()
    {
        return self::REQUIREJS_CONFIG_CACHE_KEY;
    }
}
