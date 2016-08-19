<?php

namespace Oro\Bundle\FeatureToggleBundle\Configuration;

use Doctrine\Common\Cache\CacheProvider;
use Oro\Component\Config\Merger\ConfigurationMerger;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;

class ConfigurationProvider
{
    /**
     * @var array
     */
    protected $rawConfiguration;

    /**
     * @var array
     */
    protected $kernelBundles;

    /**
     * @var CacheProvider
     */
    protected $cache;

    /**
     * @param array $rawConfiguration
     * @param array $kernelBundles
     * @param CacheProvider $cache
     */
    public function __construct(
        array $rawConfiguration,
        array $kernelBundles,
        CacheProvider $cache
    ) {
        $this->rawConfiguration = $rawConfiguration;
        $this->kernelBundles = array_values($kernelBundles);
        $this->cache = $cache;
    }

    public function warmUpCache()
    {
        $this->clearCache();
        $this->cache->save(FeatureToggleConfiguration::ROOT, $this->resolveConfiguration());
    }

    public function clearCache()
    {
        $this->cache->delete(FeatureToggleConfiguration::ROOT);
    }

    /**
     * @param bool $ignoreCache
     * @return array
     * @throws InvalidConfigurationException
     */
    public function getConfiguration($ignoreCache = false)
    {
        if (!$ignoreCache && $this->cache->contains(FeatureToggleConfiguration::ROOT)) {
            $configuration = $this->cache->fetch(FeatureToggleConfiguration::ROOT);
        } else {
            $configuration = $this->resolveConfiguration();

            if (!$ignoreCache) {
                $this->warmUpCache();
            }
        }

        return $configuration;
    }

    /**
     * @return array
     */
    protected function resolveConfiguration()
    {
        $merger = new ConfigurationMerger($this->kernelBundles);
        $configs = $merger->mergeConfiguration($this->rawConfiguration);
        $data = [];

        if (!empty($configs)) {
            $processor = new Processor();
            $configuration = new FeatureToggleConfiguration();
            $data = $processor->processConfiguration($configuration, [$configs]);
        }

        return $data;
    }
}
