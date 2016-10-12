<?php

namespace Oro\Bundle\DataGridBundle\Provider;

use Symfony\Component\DependencyInjection\ContainerBuilder;

use Doctrine\Common\Cache\CacheProvider;

use Oro\Component\Config\Loader\CumulativeConfigLoader;
use Oro\Component\Config\Loader\YamlCumulativeFileLoader;
use Oro\Component\PhpUtils\ArrayUtil;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Exception\RuntimeException;

class ConfigurationProvider implements ConfigurationProviderInterface
{
    const COMPILER_PASS_NAME = 'oro_datagrid';
    const CACHE_POSTFIX      = 'data';
    const ROOT_PARAMETER     = 'datagrids';
    const LOADED_FLAG        = 'loaded';

    /** @var array */
    protected $rawConfiguration = [];

    /** @var SystemAwareResolver */
    protected $resolver;

    /** @var array */
    protected $processedConfiguration = [];

    /** @var CacheProvider  */
    private $cache;

    /**
     * Constructor
     *
     * @param SystemAwareResolver $resolver
     * @param CacheProvider       $cache
     */
    public function __construct(SystemAwareResolver $resolver, CacheProvider $cache)
    {
        $this->resolver         = $resolver;
        $this->cache            = $cache;
    }

    /**
     * {@inheritDoc}
     */
    public function isApplicable($gridName)
    {
        $this->ensureConfigurationLoaded($gridName);
        return isset($this->rawConfiguration[$gridName]);
    }

    /**
     * {@inheritDoc}
     */
    public function getConfiguration($gridName)
    {
        if (!isset($this->processedConfiguration[$gridName])) {
            $rawConfiguration = $this->getRawConfiguration($gridName);
            $config = $this->resolver->resolve($gridName, $rawConfiguration);

            $this->processedConfiguration[$gridName] = $config;
        }

        return DatagridConfiguration::createNamed($gridName, $this->processedConfiguration[$gridName]);
    }

    /**
     * @param string $gridName
     *
     * @return array
     */
    public function getRawConfiguration($gridName)
    {
        if (!$this->isApplicable($gridName)) {
            throw new RuntimeException(sprintf('A configuration for "%s" datagrid was not found.', $gridName));
        }

        return $this->rawConfiguration[$gridName];
    }

    /**
     * Make sure that configuration saved to cache
     * @param string $gridName
     */
    protected function ensureConfigurationLoaded($gridName)
    {
        if (!isset($this->rawConfiguration[$gridName])) {
            if (!$this->cache->contains(self::LOADED_FLAG) || !$this->cache->fetch(self::LOADED_FLAG)) {
                $this->loadConfiguration();
            }

            $data = $this->cache->fetch($gridName.'_'.self::CACHE_POSTFIX);
            if ($data) {
                $this->rawConfiguration = array_merge($this->rawConfiguration, $data);
            }
        }
    }

    /**
     * Loads configurations and save them in cache
     *
     * @param ContainerBuilder $container The container builder
     *                                    If NULL the loaded resources will not be registered in the container
     *                                    and as result will not be monitored for changes
     */
    public function loadConfiguration(ContainerBuilder $container = null)
    {
        $config = [];
        $configLoader = self::getDatagridConfigurationLoader();
        $resources    = $configLoader->load($container);
        foreach ($resources as $resource) {
            if (isset($resource->data[self::ROOT_PARAMETER])
                && is_array($resource->data[self::ROOT_PARAMETER])
                && $this->cache
            ) {
                $config = ArrayUtil::arrayMergeRecursiveDistinct($config, $resource->data[self::ROOT_PARAMETER]);
            }
        }

        $this->rawConfiguration = $config;
        $this->aggregateGridCacheConfig();
    }

    /**
     * Group grid config with their mixins and save to cache
     */
    protected function aggregateGridCacheConfig()
    {
        foreach ($this->rawConfiguration as $gridName => $gridConfig) {
            $aggregatedConfig = [];
            $aggregatedConfig[$gridName] = $gridConfig;
            if (isset($gridConfig['mixins'])) {
                $mixins = $gridConfig['mixins'];
                if (is_array($mixins)) {
                    foreach ($mixins as $mixin) {
                        $aggregatedConfig[$mixin] = $this->rawConfiguration[$mixin];
                    }
                } elseif (is_string($mixins)) {
                    $aggregatedConfig[$mixins] = $this->rawConfiguration[$mixins];
                }
            }
            $this->cache->save($gridName.'_'.self::CACHE_POSTFIX, $aggregatedConfig);
        }

        $this->cache->save(self::LOADED_FLAG, true);
    }

    /**
     * @return CumulativeConfigLoader
     */
    public static function getDatagridConfigurationLoader()
    {
        return new CumulativeConfigLoader(
            self::COMPILER_PASS_NAME,
            new YamlCumulativeFileLoader('Resources/config/oro/datagrids.yml')
        );
    }
}
