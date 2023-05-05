<?php

namespace Oro\Bundle\ApiBundle\Provider;

use Oro\Bundle\ApiBundle\Config\Definition\ApiConfiguration as Config;
use Oro\Bundle\ApiBundle\Config\Extension\ConfigExtensionRegistry;
use Oro\Component\Config\Loader\CumulativeConfigLoader;
use Oro\Component\Config\Loader\FolderYamlCumulativeFileLoader;
use Oro\Component\Config\Loader\YamlCumulativeFileLoader;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Config\Resource\ResourceInterface;

/**
 * Warms up API configuration cache based on "config_files" section in the bundle configuration
 * and "entity_aliases", "exclusions" and "inclusions" sections
 * in "Resources/config/oro/api.yml" files.
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class ConfigCacheWarmer
{
    public const CONFIG_KEY = 'configKey';
    public const CONFIG = 'config';
    public const ALIASES = 'aliases';
    public const SUBSTITUTIONS = 'substitutions';
    public const EXCLUDED_ENTITIES = 'excluded_entities';
    public const EXCLUSIONS = 'exclusions';
    public const INCLUSIONS = 'inclusions';

    private const OVERRIDE_CLASS = 'override_class';
    private const APP_API_CONFIG_PATH = '../config/oro/';
    private const YAML_EXTENSION = '.yml';

    private ConfigExtensionRegistry $configExtensionRegistry;
    /** @var array [config key => [config file name, ...], ...] */
    private array $configFiles;
    private ConfigCacheFactory $configCacheFactory;
    private bool $debug;
    private string $environment;
    /** @var ResourceInterface[]|null [config file name => ResourceInterface, ...] */
    private ?array $resources = null;

    public function __construct(
        array $configFiles,
        ConfigExtensionRegistry $configExtensionRegistry,
        ConfigCacheFactory $configCacheFactory,
        bool $debug,
        string $environment
    ) {
        $this->configExtensionRegistry = $configExtensionRegistry;
        $this->configFiles = $configFiles;
        $this->configCacheFactory = $configCacheFactory;
        $this->debug = $debug;
        $this->environment = $environment;
    }

    public function warmUp(string $configKey = null): void
    {
        $configFiles = $this->configFiles;
        if ($configKey) {
            if (!isset($configFiles[$configKey])) {
                throw new \InvalidArgumentException(sprintf('Unknown config key "%s".', $configKey));
            }
            $configFiles = [$configKey => $configFiles[$configKey]];
        }

        $this->resources = [];
        try {
            $fileConfigs = $this->loadConfigFiles($configFiles);
            foreach ($configFiles as $key => $fileNames) {
                $this->dumpConfigCache($key, $fileNames, $fileConfigs);
            }
        } finally {
            $this->resources = null;
        }
    }

    /**
     * @param string   $configKey
     * @param string[] $fileNames
     * @param array    $fileConfigs
     */
    private function dumpConfigCache(string $configKey, array $fileNames, array $fileConfigs): void
    {
        if (\count($fileNames) === 1) {
            $this->dumpConfigCacheForSingleFileApi($configKey, $fileNames[0], $fileConfigs[$fileNames[0]]);
        } else {
            $this->dumpConfigCacheForMultiFileApi($configKey, $fileNames, $fileConfigs);
        }
    }

    private function dumpConfigCacheForSingleFileApi(string $configKey, string $fileName, array $fileConfig): void
    {
        $config = $fileConfig[self::CONFIG];
        $aliases = $fileConfig[self::ALIASES];
        foreach ($fileConfig[self::SUBSTITUTIONS] as $overriddenEntityClass => $entityClass) {
            $aliases[$overriddenEntityClass] = [];
            if (!isset($config[Config::ENTITIES_SECTION][$overriddenEntityClass])) {
                $config[Config::ENTITIES_SECTION][$overriddenEntityClass] = [];
            }
        }

        $this->dumpConfigCacheFile(
            $configKey,
            [$fileName => $config],
            $aliases,
            $fileConfig[self::SUBSTITUTIONS],
            $fileConfig[self::EXCLUSIONS],
            $fileConfig[self::INCLUSIONS]
        );
    }

    /**
     * @param string   $configKey
     * @param string[] $fileNames
     * @param array    $fileConfigs
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    private function dumpConfigCacheForMultiFileApi(string $configKey, array $fileNames, array $fileConfigs): void
    {
        $allAliases = [];
        $allSubstitutions = [];
        $allExclusions = [];
        $allInclusions = [];
        foreach ($fileNames as $fileName) {
            $fileConfig = $fileConfigs[$fileName];
            foreach ($fileConfig[self::ALIASES] as $entityClass => $alias) {
                if (!isset($allAliases[$entityClass])) {
                    $allAliases[$entityClass] = $alias;
                }
            }
            foreach ($fileConfig[self::SUBSTITUTIONS] as $overriddenEntityClass => $entityClass) {
                if (!isset($allSubstitutions[$overriddenEntityClass])) {
                    $allSubstitutions[$overriddenEntityClass] = $entityClass;
                }
            }
            $exclusions = $this->processInclusionOrExclusion(
                $fileConfig[self::EXCLUSIONS],
                $allExclusions,
                $allInclusions
            );
            $inclusions = $this->processInclusionOrExclusion(
                $fileConfig[self::INCLUSIONS],
                $allInclusions,
                $allExclusions
            );
            if ($exclusions) {
                /** @noinspection SlowArrayOperationsInLoopInspection */
                $allExclusions = array_merge($allExclusions, $exclusions);
            }
            if ($inclusions) {
                /** @noinspection SlowArrayOperationsInLoopInspection */
                $allInclusions = array_merge($allInclusions, $inclusions);
            }
        }

        $firstFileName = null;
        foreach ($fileNames as $fileName) {
            if (!isset($fileConfigs[$fileName][self::CONFIG_KEY])) {
                $firstFileName = $fileName;
                break;
            }
        }

        foreach ($allSubstitutions as $overriddenEntityClass => $entityClass) {
            $allAliases[$overriddenEntityClass] = [];
            if ($firstFileName && !$this->hasEntityConfig($overriddenEntityClass, $fileNames, $fileConfigs)) {
                $fileConfigs[$firstFileName][self::CONFIG][Config::ENTITIES_SECTION][$overriddenEntityClass] = [];
            }
        }

        $allConfigs = [];
        foreach ($fileNames as $fileName) {
            if (!empty($fileConfigs[$fileName][self::CONFIG])) {
                $allConfigs[$fileName] = $fileConfigs[$fileName][self::CONFIG];
            }
        }

        $this->dumpConfigCacheFile(
            $configKey,
            $allConfigs,
            $allAliases,
            $allSubstitutions,
            $allExclusions,
            $allInclusions
        );
    }

    private function dumpConfigCacheFile(
        string $configKey,
        array $config,
        array $aliases,
        array $substitutions,
        array $exclusions,
        array $inclusions
    ): void {
        $resources = null;
        if ($this->debug) {
            $resources = [];
            foreach ($this->configFiles[$configKey] as $fileName) {
                $resources[] = $this->resources[$fileName];
            }
        }
        $data = [
            self::CONFIG            => $config,
            self::ALIASES           => $aliases,
            self::SUBSTITUTIONS     => $substitutions,
            self::EXCLUDED_ENTITIES => array_unique(array_column($exclusions, 'entity')),
            self::EXCLUSIONS        => $exclusions,
            self::INCLUSIONS        => $inclusions
        ];
        $cache = $this->configCacheFactory->getCache($configKey);
        $cache->write(
            sprintf('<?php return %s;', var_export($data, true)),
            $resources
        );
    }

    /**
     * @param array $configFiles [config key => [config file name, ...], ...]
     *
     * @return array [config file name => [config key, config, aliases, substitutions, exclusions, inclusions], ...]
     */
    private function loadConfigFiles(array $configFiles): array
    {
        $fileConfigs = [];
        $fileConfigMap = [];
        foreach ($configFiles as $configKey => $fileNames) {
            foreach ($fileNames as $fileName) {
                if (!\array_key_exists($fileName, $fileConfigs)) {
                    $requestConfig = $this->loadConfigFile($fileName);

                    $aliases = $requestConfig[Config::ENTITY_ALIASES_SECTION];
                    unset($requestConfig[Config::ENTITY_ALIASES_SECTION]);
                    $exclusions = $requestConfig[Config::EXCLUSIONS_SECTION];
                    unset($requestConfig[Config::EXCLUSIONS_SECTION]);
                    $inclusions = $requestConfig[Config::INCLUSIONS_SECTION];
                    unset($requestConfig[Config::INCLUSIONS_SECTION]);

                    $substitutions = [];
                    foreach ($aliases as $entityClass => $alias) {
                        if (\array_key_exists(self::OVERRIDE_CLASS, $alias)) {
                            $substitutions[$alias[self::OVERRIDE_CLASS]] = $entityClass;
                            unset($aliases[$entityClass][self::OVERRIDE_CLASS]);
                        }
                    }

                    $fileConfigs[$fileName] = [
                        self::CONFIG        => $requestConfig,
                        self::ALIASES       => $aliases,
                        self::SUBSTITUTIONS => $substitutions,
                        self::EXCLUSIONS    => $exclusions,
                        self::INCLUSIONS    => $inclusions
                    ];
                }
            }
            if (\count($fileNames) === 1) {
                $fileConfigMap[reset($fileNames)] = $configKey;
            }
        }
        foreach ($fileConfigMap as $fileName => $configKey) {
            $fileConfigs[$fileName][self::CONFIG_KEY] = $configKey;
        }

        return $fileConfigs;
    }

    private function loadConfigFile(string $fileName): array
    {
        $configFileLoaders = [new YamlCumulativeFileLoader('Resources/config/oro/' . $fileName)];
        if ('test' === $this->environment) {
            $configFileLoaders[] = new YamlCumulativeFileLoader('Tests/Functional/Environment/' . $fileName);
        }
        /** Load api configurations from application */
        if (str_contains($fileName, self::YAML_EXTENSION)) {
            $fileNameBody = str_replace(self::YAML_EXTENSION, '', $fileName);
            $configFileLoaders[] = new FolderYamlCumulativeFileLoader(
                self::APP_API_CONFIG_PATH . $fileNameBody
            );
        }

        $config = [];
        $configLoader = new CumulativeConfigLoader('oro_api', $configFileLoaders);
        $resources = $configLoader->load();
        foreach ($resources as $resource) {
            if (\array_key_exists(Config::ROOT_NODE, $resource->data)) {
                $config[] = $resource->data[Config::ROOT_NODE];
            }
        }
        $this->resources[$fileName] = $configLoader->getResources();

        return $this->processConfiguration(
            new Config($this->configExtensionRegistry),
            $config
        );
    }

    private function processConfiguration(ConfigurationInterface $configuration, array $configs): array
    {
        return (new Processor())->processConfiguration($configuration, $configs);
    }

    /**
     * @param string   $entityClass
     * @param string[] $fileNames
     * @param array    $fileConfigs
     *
     * @return bool
     */
    private function hasEntityConfig(string $entityClass, array $fileNames, array $fileConfigs): bool
    {
        $hasConfig = false;
        foreach ($fileNames as $fileName) {
            if (isset($fileConfigs[$fileName][self::CONFIG][Config::ENTITIES_SECTION][$entityClass])) {
                $hasConfig = true;
                break;
            }
        }

        return $hasConfig;
    }

    private function processInclusionOrExclusion(
        array $items,
        array $existingItems,
        array $existingInverseItems
    ): array {
        $newItems = [];
        foreach ($items as $item) {
            if (!$this->hasInclusionOrExclusion($item, $existingItems)
                && !$this->hasInclusionOrExclusion($item, $existingInverseItems)
            ) {
                $newItems[] = $item;
            }
        }

        return $newItems;
    }

    private function hasInclusionOrExclusion(array $item, array $items): bool
    {
        $exist = false;
        foreach ($items as $existingItem) {
            if (\count($existingItem) !== \count($item)) {
                continue;
            }
            $equal = true;
            foreach ($item as $key => $value) {
                if (!\array_key_exists($key, $existingItem) || $existingItem[$key] !== $value) {
                    $equal = false;
                    break;
                }
            }
            if ($equal) {
                $exist = true;
                break;
            }
        }

        return $exist;
    }
}
