<?php

namespace Oro\Bundle\ApiBundle\Provider;

use Oro\Bundle\ApiBundle\Config\ConfigExtensionRegistry;
use Oro\Bundle\ApiBundle\Config\Definition\ApiConfiguration as Config;
use Oro\Component\Config\Loader\CumulativeConfigLoader;
use Oro\Component\Config\Loader\YamlCumulativeFileLoader;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Config\Resource\ResourceInterface;

/**
 * Warms up Data API configuration cache based on "config_files" section in the bundle configuration
 * and "entity_aliases", "exclusions" and "inclusions" sections
 * in "Resources/config/oro/api.yml" files.
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class ConfigCacheWarmer
{
    public const CONFIG_KEY        = 'configKey';
    public const CONFIG            = 'config';
    public const ALIASES           = 'aliases';
    public const SUBSTITUTIONS     = 'substitutions';
    public const EXCLUDED_ENTITIES = 'excluded_entities';
    public const EXCLUSIONS        = 'exclusions';
    public const INCLUSIONS        = 'inclusions';

    private const OVERRIDE_CLASS = 'override_class';

    /** @var ConfigExtensionRegistry */
    private $configExtensionRegistry;

    /** @var array [config key => [config file name, ...], ...] */
    private $configFiles;

    /** @var ConfigCacheFactory */
    private $configCacheFactory;

    /** @var bool */
    private $debug;

    /** @var string */
    private $environment;

    /** @var ResourceInterface[] [config file name => ResourceInterface, ...] */
    private $resources;

    /**
     * @param array                   $configFiles
     * @param ConfigExtensionRegistry $configExtensionRegistry
     * @param ConfigCacheFactory      $configCacheFactory
     * @param bool                    $debug
     * @param string                  $environment
     */
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


    /**
     * @param string|null $configKey
     */
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
        if (count($fileNames) === 1) {
            $this->dumpConfigCacheForSingleFileApi($configKey, $fileNames[0], $fileConfigs[$fileNames[0]]);
        } else {
            $this->dumpConfigCacheForMultiFileApi($configKey, $fileNames, $fileConfigs);
        }
    }

    /**
     * @param string $configKey
     * @param string $fileName
     * @param array  $fileConfig
     */
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
            foreach ($fileConfig[self::EXCLUSIONS] as $exclusion) {
                if (!$this->hasInclusionOrExclusion($exclusion, $allExclusions)) {
                    $allExclusions[] = $exclusion;
                }
            }
            foreach ($fileConfig[self::INCLUSIONS] as $inclusion) {
                if (!$this->hasInclusionOrExclusion($inclusion, $allInclusions)) {
                    $allInclusions[] = $inclusion;
                }
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

    /**
     * @param string $configKey
     * @param array  $config
     * @param array  $aliases
     * @param array  $substitutions
     * @param array  $exclusions
     * @param array  $inclusions
     */
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
                if (!array_key_exists($fileName, $fileConfigs)) {
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
            if (count($fileNames) === 1) {
                $fileConfigMap[reset($fileNames)] = $configKey;
            }
        }
        foreach ($fileConfigMap as $fileName => $configKey) {
            $fileConfigs[$fileName][self::CONFIG_KEY] = $configKey;
        }

        return $fileConfigs;
    }

    /**
     * @param string $fileName
     *
     * @return array []
     */
    private function loadConfigFile(string $fileName): array
    {
        $configFileLoaders = [new YamlCumulativeFileLoader('Resources/config/oro/' . $fileName)];
        if ('test' === $this->environment) {
            $configFileLoaders[] = new YamlCumulativeFileLoader('Tests/Functional/Environment/' . $fileName);
        }

        $config = [];
        $configLoader = new CumulativeConfigLoader('oro_api', $configFileLoaders);
        $resources = $configLoader->load();
        foreach ($resources as $resource) {
            if (array_key_exists(Config::ROOT_NODE, $resource->data)) {
                $config[] = $resource->data[Config::ROOT_NODE];
            }
        }
        $this->resources[$fileName] = $configLoader->getResources();

        return $this->processConfiguration(
            new Config($this->configExtensionRegistry),
            $config
        );
    }

    /**
     * @param ConfigurationInterface $configuration
     * @param array                  $configs
     *
     * @return array
     */
    private function processConfiguration(ConfigurationInterface $configuration, array $configs): array
    {
        $processor = new Processor();

        return $processor->processConfiguration($configuration, $configs);
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
        foreach ($fileNames as $key => $fileName) {
            if (isset($fileConfigs[$fileName][self::CONFIG][Config::ENTITIES_SECTION][$entityClass])) {
                $hasConfig = true;
                break;
            }
        }

        return $hasConfig;
    }

    /**
     * @param array $item
     * @param array $items
     *
     * @return bool
     */
    private function hasInclusionOrExclusion(array $item, array $items): bool
    {
        $exist = false;
        foreach ($items as $existingItem) {
            if (count($existingItem) !== count($item)) {
                continue;
            }
            $equal = true;
            foreach ($item as $key => $value) {
                if (!array_key_exists($key, $existingItem) || $existingItem[$key] !== $value) {
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
