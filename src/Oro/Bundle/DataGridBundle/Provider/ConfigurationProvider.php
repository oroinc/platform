<?php

namespace Oro\Bundle\DataGridBundle\Provider;

use Doctrine\Common\Cache\CacheProvider;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Exception\RuntimeException;
use Oro\Component\Config\Loader\CumulativeConfigLoader;
use Oro\Component\Config\Loader\YamlCumulativeFileLoader;
use Oro\Component\PhpUtils\ArrayUtil;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Load data grids configuration from Resources/config/oro/datagrids.yml files
 */
class ConfigurationProvider implements ConfigurationProviderInterface
{
    private const ROOT_PARAMETER   = 'datagrids';
    private const MIXINS_PARAMETER = 'mixins';

    /** @var array */
    private $rawConfiguration = [];

    /** @var SystemAwareResolver */
    private $resolver;

    /** @var array */
    private $processedConfiguration = [];

    /** @var CacheProvider */
    private $cache;

    /**
     * Constructor
     *
     * @param SystemAwareResolver $resolver
     * @param CacheProvider       $cache
     */
    public function __construct(SystemAwareResolver $resolver, CacheProvider $cache)
    {
        $this->resolver = $resolver;
        $this->cache = $cache;
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
     *
     * @param string $gridName
     */
    private function ensureConfigurationLoaded($gridName)
    {
        if (!isset($this->rawConfiguration[$gridName])) {
            $data = $this->cache->fetch($gridName);
            if (false === $data) {
                $this->loadConfiguration();
                $data = $this->cache->fetch($gridName);
            }
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
        $configLoader = $this->getDatagridConfigurationLoader();
        $resources = $configLoader->load($container);
        foreach ($resources as $resource) {
            if (isset($resource->data[self::ROOT_PARAMETER])) {
                $grids = $resource->data[self::ROOT_PARAMETER];
                if (is_array($grids)) {
                    $config = ArrayUtil::arrayMergeRecursiveDistinct($config, $grids);
                }
            }
        }

        $this->rawConfiguration = $config;
        $this->aggregateGridCacheConfig();
    }

    /**
     * Group grid config with their mixins and save to cache
     */
    private function aggregateGridCacheConfig()
    {
        foreach ($this->rawConfiguration as $gridName => $gridConfig) {
            $aggregatedConfig = [];
            $aggregatedConfig[$gridName] = $gridConfig;
            if (isset($gridConfig[self::MIXINS_PARAMETER])) {
                $mixins = $gridConfig[self::MIXINS_PARAMETER];
                if (is_array($mixins)) {
                    foreach ($mixins as $mixin) {
                        $aggregatedConfig[$mixin] = $this->rawConfiguration[$mixin];
                    }
                } elseif (is_string($mixins)) {
                    $aggregatedConfig[$mixins] = $this->rawConfiguration[$mixins];
                }
            }
            $this->cache->save($gridName, $aggregatedConfig);
        }
    }

    /**
     * @return CumulativeConfigLoader
     */
    private function getDatagridConfigurationLoader()
    {
        return new CumulativeConfigLoader(
            'oro_datagrid',
            new YamlCumulativeFileLoader('Resources/config/oro/datagrids.yml')
        );
    }
}
