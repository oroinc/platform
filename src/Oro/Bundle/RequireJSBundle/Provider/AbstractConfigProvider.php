<?php

namespace Oro\Bundle\RequireJSBundle\Provider;

use Doctrine\Common\Cache\CacheProvider;

use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;

use Symfony\Component\Yaml\Yaml;

use Oro\Bundle\RequireJSBundle\Config\Config as RequireJSConfig;

use Oro\Component\PhpUtils\ArrayUtil;

abstract class AbstractConfigProvider implements ConfigProviderInterface
{
    /**
     * @var CacheProvider
     */
    protected $cache;

    /**
     * @var EngineInterface
     */
    protected $templateEngine;

    /**
     * @var array
     */
    protected $config;

    /**
     * @var array
     */
    protected $bundles;

    /**
     * @var string
     */
    protected $webRoot;

    /**
     * @param EngineInterface   $templateEngine
     * @param CacheProvider     $cache
     * @param array             $config
     * @param array             $bundles
     * @param string            $webRoot
     */
    public function __construct(
        EngineInterface $templateEngine,
        CacheProvider $cache,
        array $config,
        array $bundles,
        $webRoot
    ) {
        $this->templateEngine = $templateEngine;
        $this->cache = $cache;
        $this->config = $config;
        $this->bundles = $bundles;
        $this->webRoot = $webRoot . DIRECTORY_SEPARATOR;
    }

    /**
     * Get config files from bundle
     *
     * @param string $bundle
     *
     * @return array
     */
    abstract protected function getFiles($bundle);

    /**
     * @return string
     */
    abstract protected function getCacheKey();

    /**
     * Fetch collected configs from cache
     *
     * @return RequireJSConfig[]
     */
    protected function getConfigs()
    {
        if (!$this->cache->contains($this->getCacheKey())) {
            $this->cache->save($this->getCacheKey(), $this->collectConfigs());
        }

        return $this->cache->fetch($this->getCacheKey());
    }

    /**
     * Create RequireJS config
     *
     * @param string $configFilePath
     * @param string $outputFilePath
     *
     * @return RequireJSConfig
     */
    protected function createRequireJSConfig($configFilePath, $outputFilePath)
    {
        $this->collectBundlesConfig();

        $config = new RequireJSConfig();
        $config->setConfigFilePath($configFilePath);
        $config->setOutputFilePath($outputFilePath);

        $this->collectMainConfig($config);
        $this->collectBuildConfig($config);

        return $config;
    }

    /**
     * Collect require.js main config
     *
     * @param RequireJSConfig $config
     *
     * @return ConfigProvider
     */
    protected function collectMainConfig(RequireJSConfig $config)
    {
        $mainConfig = $this->config['config'];
        if (!empty($mainConfig['paths']) && is_array($mainConfig['paths'])) {
            foreach ($mainConfig['paths'] as $key => $path) {
                if (substr($path, 0, 8) === 'bundles/') {
                    $path = substr($path, 8);
                }

                if (substr($path, -3) === '.js') {
                    $path = substr($path, 0, -3);
                }

                $mainConfig['paths'][$key] = $path;
            }
        }

        $config->setMainConfig(
            $this->templateEngine->render('OroRequireJSBundle::require_config.js.twig', ['config' => $mainConfig])
        );

        return $this;
    }

    /**
     * Collect require.js build config
     *
     * @param RequireJSConfig $config
     *
     * @return ConfigProvider
     */
    protected function collectBuildConfig(RequireJSConfig $config)
    {
        $buildConfig = $this->config['build'];

        $paths = [
            'require-config'    => $this->webRoot . substr($config->getConfigFilePath(), 0, -3),
            'require-lib'       => 'ororequirejs/lib/require',
        ];

        $buildConfig = array_merge(
            $buildConfig,
            [
                'baseUrl'           => $this->webRoot . 'bundles',
                'out'               => $this->webRoot . $config->getOutputFilePath(),
                'mainConfigFile'    => $this->webRoot . $config->getConfigFilePath(),
                'include'           => [],
                'paths'             => array_merge($this->config['build']['paths'], $paths)
            ]
        );

        if (isset($this->config['config']['paths'])) {
            $buildConfig['include'] = array_merge(
                array_keys($paths),
                array_keys($this->config['config']['paths'])
            );
        }

        $config->setBuildConfig($buildConfig);

        return $this;
    }

    /**
     * Collect require.js config from all bundles
     *
     * @return ConfigProvider
     */
    protected function collectBundlesConfig()
    {
        foreach ($this->bundles as $bundle) {
            foreach ($this->getFiles($bundle) as $file) {
                $config = Yaml::parse(file_get_contents(realpath($file)));
                $this->config = ArrayUtil::arrayMergeRecursiveDistinct($this->config, $config);
            }
        }

        return $this;
    }
}
