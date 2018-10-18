<?php

namespace Oro\Bundle\FeatureToggleBundle\Configuration;

use Doctrine\Common\Cache\CacheProvider;
use Oro\Bundle\FeatureToggleBundle\Exception\CircularReferenceException;
use Oro\Component\Config\Merger\ConfigurationMerger;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

/**
 * Provides an entry point for configuration of features.
 */
class ConfigurationProvider
{
    const INTERNAL = '__internal__';
    const FEATURES = '__features__';
    const BY_RESOURCE = 'by_resource';
    const DEPENDENCIES = 'dependencies';
    const DEPENDENT_FEATURES = 'dependent_features';
    const DEPENDENCY_KEY = 'dependencies';

    /**
     * @var array
     */
    protected $rawConfiguration;

    /**
     * @var array
     */
    protected $kernelBundles;

    /**
     * @var FeatureToggleConfiguration
     */
    protected $configuration;

    /**
     * @var CacheProvider
     */
    protected $cache;

    /**
     * @param array $rawConfiguration
     * @param array $kernelBundles
     * @param FeatureToggleConfiguration $configuration
     * @param CacheProvider $cache
     */
    public function __construct(
        array $rawConfiguration,
        array $kernelBundles,
        FeatureToggleConfiguration $configuration,
        CacheProvider $cache
    ) {
        $this->rawConfiguration = $rawConfiguration;
        $this->kernelBundles = array_values($kernelBundles);
        $this->configuration = $configuration;
        $this->cache = $cache;
    }

    public function warmUpCache()
    {
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
     */
    public function getDependentsConfiguration($ignoreCache = false)
    {
        return $this->getConfiguration($ignoreCache)[self::INTERNAL][self::DEPENDENT_FEATURES];
    }

    /**
     * @param bool $ignoreCache
     * @return array
     * @throws InvalidConfigurationException
     */
    protected function getConfiguration($ignoreCache = false)
    {
        if ($ignoreCache) {
            $configuration = $this->resolveConfiguration();
        } else {
            $configuration = $this->cache->fetch(FeatureToggleConfiguration::ROOT);
            if (false === $configuration) {
                $configuration = $this->resolveConfiguration();
                $this->cache->save(FeatureToggleConfiguration::ROOT, $configuration);
            }
        }

        return $configuration;
    }

    /**
     * @return array
     */
    protected function resolveConfiguration()
    {
        $data = [
            self::FEATURES => [],
            self::INTERNAL => [
                self::DEPENDENCIES => [],
                self::BY_RESOURCE => []
            ]
        ];
        $configs = $this->getMergedConfigs();
        if (count($configs) > 0) {
            $data[self::FEATURES] = $this->configuration->processConfiguration($configs);
            $data[self::INTERNAL][self::DEPENDENCIES] = $this->resolveDependencies($data[self::FEATURES]);
            $data[self::INTERNAL][self::DEPENDENT_FEATURES] = $this->resolveDependentFeatures(
                $data[self::INTERNAL][self::DEPENDENCIES]
            );
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
     * @param array $data
     * @return array
     * @throws CircularReferenceException
     */
    protected function resolveDependentFeatures(array $data)
    {
        $featureDependents = [];
        foreach (array_keys($data) as $feature) {
            $dependent = $this->getFeatureDependents($feature, $data);
            $featureDependents[$feature] = array_unique($dependent);
        }

        return $featureDependents;
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

    /**
     * @param string $feature
     * @param array $dependenciesData
     * @return array
     */
    protected function getFeatureDependents($feature, array $dependenciesData)
    {
        $depended = [];
        foreach ($dependenciesData as $featureName => $dependencies) {
            if (in_array($feature, $dependencies, true)) {
                $depended[] = $featureName;
            }
        }

        return $depended;
    }

    /**
     * @return array
     */
    protected function getMergedConfigs()
    {
        $merger = new ConfigurationMerger($this->kernelBundles);

        return $merger->mergeConfiguration($this->rawConfiguration);
    }
}
