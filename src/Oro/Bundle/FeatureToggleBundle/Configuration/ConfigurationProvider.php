<?php

namespace Oro\Bundle\FeatureToggleBundle\Configuration;

use Oro\Component\Config\Cache\PhpArrayConfigProvider;
use Oro\Component\Config\CumulativeResourceManager;
use Oro\Component\Config\Loader\CumulativeConfigProcessorUtil;
use Oro\Component\Config\Loader\Factory\CumulativeConfigLoaderFactory;
use Oro\Component\Config\Merger\ConfigurationMerger;
use Oro\Component\Config\ResourcesContainerInterface;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

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
    private const TOGGLES = 'toggles';
    private const TOGGLE = 'toggle';

    private FeatureToggleConfiguration $configuration;
    private ConfigurationExtension $configurationExtension;

    public function __construct(
        string $cacheFile,
        bool $debug,
        FeatureToggleConfiguration $configuration,
        ConfigurationExtension $configurationExtension
    ) {
        parent::__construct($cacheFile, $debug);
        $this->configuration = $configuration;
        $this->configurationExtension = $configurationExtension;
    }

    public function getFeaturesConfiguration(): array
    {
        return $this->getConfiguration(self::FEATURES);
    }

    public function getResourcesConfiguration(): array
    {
        return $this->getInternalConfiguration(self::BY_RESOURCE);
    }

    public function getDependenciesConfiguration(): array
    {
        return $this->getInternalConfiguration(self::DEPENDENCIES);
    }

    public function getDependentsConfiguration(): array
    {
        return $this->getInternalConfiguration(self::DEPENDENT_FEATURES);
    }

    public function getTogglesConfiguration(): array
    {
        return $this->getInternalConfiguration(self::TOGGLES);
    }

    /**
     * {@inheritDoc}
     */
    public function clearCache(): void
    {
        parent::clearCache();
        $this->configurationExtension->clearConfigurationCache();
    }

    /**
     * {@inheritDoc}
     */
    protected function doLoadConfig(ResourcesContainerInterface $resourcesContainer)
    {
        $configs = [];
        $configLoader = CumulativeConfigLoaderFactory::create('oro_features', self::CONFIG_FILE);
        $resources = $configLoader->load($resourcesContainer);
        foreach ($resources as $resource) {
            if (!empty($resource->data[FeatureToggleConfiguration::ROOT_NODE])) {
                $configs[$resource->bundleClass] = $resource->data[FeatureToggleConfiguration::ROOT_NODE];
            }
        }
        $merger = new ConfigurationMerger($this->getBundles());
        $mergedConfig = $merger->mergeConfiguration($configs);

        $processedConfig = CumulativeConfigProcessorUtil::processConfiguration(
            self::CONFIG_FILE,
            $this->configuration,
            [$mergedConfig]
        );

        return $this->resolveConfiguration($processedConfig);
    }

    protected function getBundles(): array
    {
        return CumulativeResourceManager::getInstance()->getBundles();
    }

    private function getConfiguration(string $sectionName): array
    {
        $configuration = $this->doGetConfig();

        return $configuration[$sectionName];
    }

    private function getInternalConfiguration(string $sectionName): array
    {
        $internalConfiguration = $this->getConfiguration(self::INTERNAL);

        return $internalConfiguration[$sectionName];
    }

    private function resolveConfiguration(array $data): array
    {
        $data = $this->configurationExtension->processConfiguration($data);
        $dependencies = $this->resolveDependencies($data);

        return [
            self::FEATURES => $data,
            self::INTERNAL => [
                self::DEPENDENCIES => $dependencies,
                self::DEPENDENT_FEATURES => $this->resolveDependentFeatures($dependencies),
                self::BY_RESOURCE => $this->resolveResources($data),
                self::TOGGLES => $this->resolveToggles($data)
            ]
        ];
    }

    private function resolveResources(array $data): array
    {
        $resourceFeatures = [];
        foreach ($data as $feature => $config) {
            foreach ($config as $resourceName => $items) {
                if (\is_array($items) && $resourceName !== self::DEPENDENCY_KEY) {
                    foreach ($items as $item) {
                        $resourceFeatures[$resourceName][$item][] = $feature;
                    }
                }
            }
        }

        return $resourceFeatures;
    }

    private function resolveToggles(array $data): array
    {
        $toggles = [];
        foreach ($data as $feature => $config) {
            if (empty($config[self::TOGGLE])) {
                continue;
            }
            $toggle = $config[self::TOGGLE];
            if (isset($toggles[$toggle])) {
                throw new InvalidConfigurationException(sprintf(
                    'A toggle can be used for one feature only, but the toggle "%s" is used for two features,'
                    . ' "%s" and "%s".',
                    $toggle,
                    $toggles[$toggle],
                    $feature
                ));
            }
            $toggles[$toggle] = $feature;
        }

        return $toggles;
    }

    private function resolveDependencies(array $data): array
    {
        $featureDependencies = [];
        foreach (array_keys($data) as $feature) {
            $dependsOn = $this->getFeatureDependencies($feature, $data);
            $featureDependencies[$feature] = array_unique($dependsOn);
        }

        return $featureDependencies;
    }

    private function resolveDependentFeatures(array $data): array
    {
        $featureDependents = [];
        foreach (array_keys($data) as $feature) {
            $dependent = $this->getFeatureDependents($feature, $data);
            $featureDependents[$feature] = array_unique($dependent);
        }

        return $featureDependents;
    }

    private function getFeatureDependencies(string $feature, array $data, array $topLevelDependencies = []): array
    {
        $dependsOnFeatures = [];
        if (!empty($data[$feature][self::DEPENDENCY_KEY])) {
            $dependsOnFeatures = $data[$feature][self::DEPENDENCY_KEY];
            if (count(array_intersect($dependsOnFeatures, $topLevelDependencies)) > 0) {
                throw new InvalidConfigurationException(sprintf(
                    'The feature "%s" has circular reference on itself.',
                    $feature
                ));
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

    private function getFeatureDependents(string $feature, array $dependenciesData): array
    {
        $depended = [];
        foreach ($dependenciesData as $featureName => $dependencies) {
            if (\in_array($feature, $dependencies, true)) {
                $depended[] = $featureName;
            }
        }

        return $depended;
    }
}
