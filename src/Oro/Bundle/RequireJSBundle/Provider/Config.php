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
    const REQUIREJS_DEFAULT_KEY         = '_main';

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
    public function getOutputFilePath($config)
    {
        return $config['build_path'];
    }

    /**
     * {@inheritdoc}
     */
    public function getConfigFilePath($config)
    {
        return self::REQUIREJS_CONFIG_FILE;
    }

    /**
     * {@inheritdoc}
     */
    public function getMainConfig($key = self::REQUIREJS_DEFAULT_KEY)
    {
        if (!$this->cache) {
            $configs = $this->collectAllConfigs();
        } else {
            if (!$this->cache->contains($this->getCacheKey())) {
                $this->cache->save($this->getCacheKey(), $this->collectAllConfigs());
            }

            $configs = $this->cache->fetch($this->getCacheKey());
        }

        return $configs[$key]['mainConfig'];
    }

    /**
     * {@inheritdoc}
     */
    public function collectAllConfigs()
    {
        $configs = [
            self::REQUIREJS_DEFAULT_KEY => [
                'mainConfig'    => $this->generateMainConfig(),
                'buildConfig'   => $this->collectBuildConfig(),
            ]
        ];

        return $configs;
    }

    /**
     * Collect require.js main config
     *
     * @param string $key
     *
     * @return string
     */
    public function generateMainConfig($key = self::REQUIREJS_DEFAULT_KEY)
    {
        $config = $this->collectConfigs($key);
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
     * @param string $key
     *
     * @return array
     */
    protected function collectBuildConfig($key = self::REQUIREJS_DEFAULT_KEY)
    {
        $config = $this->collectConfigs($key);
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
            'require-config'    => $webRoot . substr($this->getConfigFilePath($config), 0, -3),
            'require-lib'       => 'ororequirejs/lib/require',
        ];

        $buildConfig = array_merge(
            $buildConfig,
            [
                'baseUrl'           => $webRoot . 'bundles',
                'out'               => $webRoot . $this->getOutputFilePath($config),
                'mainConfigFile'    => $webRoot . $this->getConfigFilePath($config),
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
     * @param string $key
     *
     * @return array
     */
    public function collectConfigs($key = self::REQUIREJS_DEFAULT_KEY)
    {
        if (!$this->configs->containsKey($key)) {
            $this->configs->set($key, $this->collectBundlesConfig($key));
        }

        return $this->configs->get($key);
    }

    /**
     * Collect require.js config from all bundles
     *
     * @param string $key
     *
     * @return array
     */
    protected function collectBundlesConfig($key = self::REQUIREJS_DEFAULT_KEY)
    {
        $config = $this->container->getParameter('oro_require_js');

        $bundles = $this->container->getParameter('kernel.bundles');
        foreach ($bundles as $bundle) {
            foreach ($this->getFiles($bundle, $key) as $file) {
                $extendedConfig = Yaml::parse(file_get_contents(realpath($file)));
                $config = ArrayUtil::arrayMergeRecursiveDistinct($config, $extendedConfig);
            }
        }

        $config['config']['config_key'] = $key;

        return $config;
    }

    /**
     * Get config files from bundle
     *
     * @param string $bundle
     * @param string $key
     * @return array
     */
    protected function getFiles($bundle, $key = self::REQUIREJS_DEFAULT_KEY)
    {
        $reflection = new \ReflectionClass($bundle);
        $file = dirname($reflection->getFileName()) . '/Resources/config/requirejs.yml';

        return is_file($file) ? [$file] : [];
    }

    /**
     * Generates build config for require.js
     *
     * @param string $configPath path to require.js main config
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
