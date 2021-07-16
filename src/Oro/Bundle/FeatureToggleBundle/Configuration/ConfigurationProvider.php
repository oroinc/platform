<?php

namespace Oro\Bundle\FeatureToggleBundle\Configuration;

use Oro\Bundle\FeatureToggleBundle\Exception\CircularReferenceException;
use Oro\Component\Config\Cache\PhpArrayConfigProvider;
use Oro\Component\Config\Loader\CumulativeConfigLoader;
use Oro\Component\Config\Loader\CumulativeConfigProcessorUtil;
use Oro\Component\Config\Loader\YamlCumulativeFileLoader;
use Oro\Component\Config\Merger\ConfigurationMerger;
use Oro\Component\Config\ResourcesContainerInterface;

/**
 * The provider for features configuration
 * that is loaded from "Resources/config/oro/features.yml" files.
 */
class ConfigurationProvider extends PhpArrayConfigProvider
{
    private const CONFIG_FILE = 'Resources/config/oro/features.yml';

    private const INTERNAL = '__internal__';
    private const FEATURES = '__features__';
    private const BY_RESOURCE = 'by_resource';
    private const DEPENDENCIES = 'dependencies';
    private const DEPENDENT_FEATURES = 'dependent_features';
    private const DEPENDENCY_KEY = 'dependencies';

    /** @var string[] */
    private $bundles;

    /** @var FeatureToggleConfiguration */
    private $configuration;

    /**
     * @param string                     $cacheFile
     * @param bool                       $debug
     * @param string[]                   $bundles
     * @param FeatureToggleConfiguration $configuration
     */
    public function __construct(
        string $cacheFile,
        bool $debug,
        array $bundles,
        FeatureToggleConfiguration $configuration
    ) {
        parent::__construct($cacheFile, $debug);
        $this->bundles = $bundles;
        $this->configuration = $configuration;
    }

    /**
     * @return array
     */
    public function getFeaturesConfiguration()
    {
        return $this->getConfiguration(self::FEATURES);
    }

    /**
     * @return array
     */
    public function getResourcesConfiguration()
    {
        return $this->getInternalConfiguration(self::BY_RESOURCE);
    }

    /**
     * @return array
     */
    public function getDependenciesConfiguration()
    {
        return $this->getInternalConfiguration(self::DEPENDENCIES);
    }

    /**
     * @return array
     */
    public function getDependentsConfiguration()
    {
        return $this->getInternalConfiguration(self::DEPENDENT_FEATURES);
    }

    /**
     * {@inheritdoc}
     */
    protected function doLoadConfig(ResourcesContainerInterface $resourcesContainer)
    {
        $configs = [];
        $configLoader = new CumulativeConfigLoader(
            'oro_features',
            new YamlCumulativeFileLoader(self::CONFIG_FILE)
        );
        $resources = $configLoader->load($resourcesContainer);
        foreach ($resources as $resource) {
            if (!empty($resource->data[FeatureToggleConfiguration::ROOT_NODE])) {
                $configs[$resource->bundleClass] = $resource->data[FeatureToggleConfiguration::ROOT_NODE];
            }
        }

        $merger = new ConfigurationMerger($this->bundles);
        $mergedConfig = $merger->mergeConfiguration($configs);

        $processedConfig = CumulativeConfigProcessorUtil::processConfiguration(
            self::CONFIG_FILE,
            $this->configuration,
            [$mergedConfig]
        );

        return $this->resolveConfiguration($processedConfig);
    }

    /**
     * @param string $sectionName
     * @return array
     */
    private function getConfiguration(string $sectionName)
    {
        $configuration = $this->doGetConfig();

        return $configuration[$sectionName];
    }

    /**
     * @param string $sectionName
     * @return array
     */
    private function getInternalConfiguration(string $sectionName)
    {
        $internalConfiguration = $this->getConfiguration(self::INTERNAL);

        return $internalConfiguration[$sectionName];
    }

    private function resolveConfiguration(array $data): array
    {
        $dependencies = $this->resolveDependencies($data);

        return [
            self::FEATURES => $data,
            self::INTERNAL => [
                self::DEPENDENCIES => $dependencies,
                self::DEPENDENT_FEATURES => $this->resolveDependentFeatures($dependencies),
                self::BY_RESOURCE => $this->resolveResources($data)
            ]
        ];
    }

    /**
     * @param array $data
     * @return array
     */
    private function resolveResources(array $data)
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
    private function resolveDependencies(array $data)
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
    private function resolveDependentFeatures(array $data)
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
    private function getFeatureDependencies($feature, array $data, array $topLevelDependencies = [])
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
    private function getFeatureDependents($feature, array $dependenciesData)
    {
        $depended = [];
        foreach ($dependenciesData as $featureName => $dependencies) {
            if (in_array($feature, $dependencies, true)) {
                $depended[] = $featureName;
            }
        }

        return $depended;
    }
}
