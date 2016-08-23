<?php

namespace Oro\Bundle\FeatureToggleBundle\Configuration;

use Doctrine\Common\Cache\CacheProvider;
use Oro\Bundle\FeatureToggleBundle\Exception\CircularReferenceException;
use Oro\Component\Config\Merger\ConfigurationMerger;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;

class ConfigurationProvider
{
    const INTERNAL = '__internal__';
    const FEATURES = '__features__';
    const BY_RESOURCE = 'by_resource';
    const DEPENDENCIES = 'dependencies';
    const DEPENDENCY_KEY = 'dependency';

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
     */
    public function getFeaturesConfiguration($ignoreCache = false)
    {
        return $this->getConfiguration($ignoreCache)[self::FEATURES];
    }

    /**
     * @param bool $ignoreCache
     * @return array
     */
    public function getResourcesConfiguration($ignoreCache = false)
    {
        return $this->getConfiguration($ignoreCache)[self::INTERNAL][self::BY_RESOURCE];
    }

    /**
     * @param bool $ignoreCache
     * @return array
     */
    public function getDependenciesConfiguration($ignoreCache = false)
    {
        return $this->getConfiguration($ignoreCache)[self::INTERNAL][self::DEPENDENCIES];
    }

    /**
     * @param bool $ignoreCache
     * @return array
     * @throws InvalidConfigurationException
     */
    protected function getConfiguration($ignoreCache = false)
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

        if (count($configs) > 0) {
            $processor = new Processor();
            $configuration = new FeatureToggleConfiguration();

            $data[self::FEATURES] = $processor->processConfiguration($configuration, [$configs]);
            $data[self::INTERNAL][self::DEPENDENCIES] = $this->resolveDependencies($data[self::FEATURES]);
            $data[self::INTERNAL][self::BY_RESOURCE] = $this->resolveResources($data[self::FEATURES]);
        }

        return $data;
    }

    /**
     * @param array $data
     * @return array
     */
    protected function resolveResources(array $data)
    {
        $resourceFeatures = [];
        foreach ($data as $feature => $resourceItems) {
            foreach ($resourceItems as $resourceName => $items) {
                if (is_array($items) && $resourceName !== self::DEPENDENCY_KEY) {
                    foreach ($items as $item) {
                        $resourceFeatures[$resourceName][$item][] = $feature;
                    }
                }
            }
        }

        return $resourceFeatures;
    }

    /**
     * @param array $data
     * @return array
     * @throws CircularReferenceException
     */
    protected function resolveDependencies(array $data)
    {
        $featureDependencies = [];
        foreach (array_keys($data) as $feature) {
            $dependsOn = $this->getFeatureDependencies($feature, $data);
            $featureDependencies[$feature] = array_unique($dependsOn);
        }

        return $featureDependencies;
    }

    /**
     * @param string $feature
     * @param array $data
     * @param array $topLevelDependencies
     * @return array
     * @throws CircularReferenceException
     */
    protected function getFeatureDependencies($feature, array $data, array $topLevelDependencies = [])
    {
        $hasDependencies = !empty($data[$feature][self::DEPENDENCY_KEY]);
        $dependsOnFeatures = [];
        if ($hasDependencies) {
            $dependsOnFeatures = $hasDependencies ? $data[$feature][self::DEPENDENCY_KEY] : [];
            if (count(array_intersect($dependsOnFeatures, $topLevelDependencies)) > 0) {
                throw new CircularReferenceException(
                    sprintf('Feature "%s" has circular reference on itself', $feature)
                );
            }

            foreach ($data[$feature][self::DEPENDENCY_KEY] as $dependsOnFeature) {
                $dependsOnFeatures = array_merge(
                    $dependsOnFeatures,
                    $this->getFeatureDependencies(
                        $dependsOnFeature,
                        $data,
                        array_merge($topLevelDependencies, $dependsOnFeatures)
                    )
                );
            }
        }

        return $dependsOnFeatures;
    }
}
