<?php

namespace Oro\Bundle\ApiBundle\DependencyInjection;

use Oro\Bundle\ApiBundle\Config\Definition\ApiConfiguration;
use Oro\Bundle\ApiBundle\Provider\CombinedConfigBag;
use Oro\Bundle\ApiBundle\Provider\ConfigBag;
use Oro\Bundle\ApiBundle\Provider\ConfigExclusionProvider;
use Oro\Bundle\ApiBundle\Provider\EntityAliasLoader;
use Oro\Bundle\ApiBundle\Provider\EntityAliasProvider;
use Oro\Bundle\ApiBundle\Provider\EntityAliasResolver;
use Oro\Bundle\ApiBundle\Provider\EntityOverrideProvider;
use Oro\Bundle\EntityBundle\Provider\AliasedEntityExclusionProvider;
use Oro\Bundle\EntityBundle\Provider\ChainExclusionProvider;
use Oro\Component\Config\Loader\CumulativeConfigLoader;
use Oro\Component\Config\Loader\YamlCumulativeFileLoader;
use Oro\Component\PhpUtils\ArrayUtil;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Configures services based on "config_files" section in the bundle configuration
 * and "entity_aliases", "exclusions" and "inclusions" sections
 * in "Resources/config/oro/api.yml" files, such as:
 * * configuration bags
 * * entity alias resolvers
 * * entity exclusion providers
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class ConfigurationLoader
{
    private const CONFIG_EXTENSION_REGISTRY_SERVICE_ID          = 'oro_api.config_extension_registry';
    private const CONFIG_BAG_REGISTRY_SERVICE_ID                = 'oro_api.config_bag_registry';
    private const ENTITY_ALIAS_RESOLVER_REGISTRY_SERVICE_ID     = 'oro_api.entity_alias_resolver_registry';
    private const ENTITY_EXCLUSION_PROVIDER_REGISTRY_SERVICE_ID = 'oro_api.entity_exclusion_provider_registry';
    private const SHARED_ENTITY_EXCLUSION_PROVIDER_SERVICE_ID   = 'oro_api.entity_exclusion_provider.shared';
    private const ENTITY_OVERRIDE_PROVIDER_REGISTRY_SERVICE_ID  = 'oro_api.entity_override_provider_registry';

    /** @var ContainerBuilder */
    private $container;

    /**
     * @param ContainerBuilder $container
     */
    public function __construct(ContainerBuilder $container)
    {
        $this->container = $container;
    }

    /**
     * @param array $config
     */
    public function load(array $config)
    {
        $configBagsConfig = [];
        $exclusionProvidersConfig = [];
        $entityAliasResolversConfig = [];
        $entityOverrideProvidersConfig = [];
        $fileConfigs = $this->loadConfigFiles($config);
        foreach ($config['config_files'] as $configKey => $fileConfig) {
            list(
                $configBagServiceId,
                $entityAliasResolverServiceId,
                $exclusionProviderServiceId,
                $entityOverrideProviderServiceId
                ) = $this->configureApi($configKey, $fileConfig['file_name'], $fileConfigs);
            $requestTypeExpression = $this->getRequestTypeExpression($fileConfig);

            $configBagsConfig[] = [$configBagServiceId, $requestTypeExpression];
            $entityAliasResolversConfig[] = [$entityAliasResolverServiceId, $requestTypeExpression];
            $exclusionProvidersConfig[] = [$exclusionProviderServiceId, $requestTypeExpression];
            $entityOverrideProvidersConfig[] = [$entityOverrideProviderServiceId, $requestTypeExpression];
        }
        $this->container->getDefinition(self::CONFIG_BAG_REGISTRY_SERVICE_ID)
            ->replaceArgument(0, $this->sortByRequestTypeExpression($configBagsConfig));
        $this->container->getDefinition(self::ENTITY_ALIAS_RESOLVER_REGISTRY_SERVICE_ID)
            ->replaceArgument(0, $this->sortByRequestTypeExpression($entityAliasResolversConfig));
        $this->container->getDefinition(self::ENTITY_EXCLUSION_PROVIDER_REGISTRY_SERVICE_ID)
            ->replaceArgument(0, $this->sortByRequestTypeExpression($exclusionProvidersConfig));
        $this->container->getDefinition(self::ENTITY_OVERRIDE_PROVIDER_REGISTRY_SERVICE_ID)
            ->replaceArgument(0, $this->sortByRequestTypeExpression($entityOverrideProvidersConfig));
    }

    /**
     * @param array $config
     *
     * @return array
     */
    private function loadConfigFiles(array $config): array
    {
        $fileConfigs = [];
        $fileConfigMap = [];
        foreach ($config['config_files'] as $configKey => $fileConfig) {
            $fileNames = $fileConfig['file_name'];
            foreach ($fileNames as $fileName) {
                if (!array_key_exists($fileName, $fileConfigs)) {
                    $requestConfig = $this->loadConfigFile($fileName);

                    $aliases = $requestConfig[ApiConfiguration::ENTITY_ALIASES_SECTION];
                    unset($requestConfig[ApiConfiguration::ENTITY_ALIASES_SECTION]);
                    $exclusions = $requestConfig[ApiConfiguration::EXCLUSIONS_SECTION];
                    unset($requestConfig[ApiConfiguration::EXCLUSIONS_SECTION]);
                    $inclusions = $requestConfig[ApiConfiguration::INCLUSIONS_SECTION];
                    unset($requestConfig[ApiConfiguration::INCLUSIONS_SECTION]);

                    $substitutions = [];
                    foreach ($aliases as $entityClass => $alias) {
                        if (\array_key_exists('override_class', $alias)) {
                            $substitutions[$alias['override_class']] = $entityClass;
                            unset($aliases[$entityClass]['override_class']);
                        }
                    }

                    $fileConfigs[$fileName] = [
                        'config'        => $requestConfig,
                        'aliases'       => $aliases,
                        'substitutions' => $substitutions,
                        'exclusions'    => $exclusions,
                        'inclusions'    => $inclusions
                    ];
                }
            }
            if (count($fileNames) === 1) {
                $fileConfigMap[reset($fileNames)] = $configKey;
            }
        }
        foreach ($fileConfigMap as $fileName => $configKey) {
            $fileConfigs[$fileName]['configKey'] = $configKey;
        }

        return $fileConfigs;
    }

    /**
     * @param string $fileName
     *
     * @return array
     */
    private function loadConfigFile(string $fileName): array
    {
        $configFileLoaders = [new YamlCumulativeFileLoader('Resources/config/oro/' . $fileName)];
        if ('test' === $this->container->getParameter('kernel.environment')) {
            $configFileLoaders[] = new YamlCumulativeFileLoader('Tests/Functional/Environment/' . $fileName);
        }

        $config = [];
        $configLoader = new CumulativeConfigLoader('oro_api', $configFileLoaders);
        $resources = $configLoader->load($this->container);
        foreach ($resources as $resource) {
            if (array_key_exists(ApiConfiguration::ROOT_NODE, $resource->data)) {
                $config[] = $resource->data[ApiConfiguration::ROOT_NODE];
            }
        }

        return $this->processConfiguration(
            new ApiConfiguration($this->container->get(self::CONFIG_EXTENSION_REGISTRY_SERVICE_ID)),
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
     * @param array $config
     *
     * @return string
     */
    private function getRequestTypeExpression(array $config): string
    {
        $requestTypes = [];
        if (!empty($config['request_type'])) {
            $requestTypes = $config['request_type'];
        }

        return implode('&', $requestTypes);
    }

    /**
     * @param string   $configKey
     * @param string[] $fileNames
     * @param array    $fileConfigs
     *
     * @return string[] [config bag service id, entity alias resolver service id, exclusion provider service id,
     *                  entity override provider service id]
     */
    private function configureApi(string $configKey, array $fileNames, array $fileConfigs): array
    {
        if (count($fileNames) === 1) {
            return $this->configureSingleFileApi($configKey, $fileConfigs[$fileNames[0]]);
        }

        return $this->configureMultiFileApi($configKey, $fileNames, $fileConfigs);
    }

    /**
     * @param string $configKey
     * @param array  $fileConfig
     *
     * @return string[] [config bag service id, entity alias resolver service id, exclusion provider service id,
     *                  entity override provider service id]
     */
    private function configureSingleFileApi(string $configKey, array $fileConfig): array
    {
        $config = $fileConfig['config'];
        $aliases = $fileConfig['aliases'];
        foreach ($fileConfig['substitutions'] as $overriddenEntityClass => $entityClass) {
            $aliases[$overriddenEntityClass] = [];
            if (!isset($config['entities'][$overriddenEntityClass])) {
                $config['entities'][$overriddenEntityClass] = [];
            }
        }

        $configBagServiceId = $this->configureConfigBag($configKey, $config);
        $entityOverrideProviderServiceId = $this->configureEntityOverrideProvider(
            $configKey,
            $fileConfig['substitutions']
        );
        $entityAliasResolverServiceId = $this->configureEntityAliasResolver(
            $configKey,
            $aliases,
            array_unique(array_column($fileConfig['exclusions'], 'entity')),
            $entityOverrideProviderServiceId
        );
        $exclusionProviderServiceId = $this->configureExclusionProvider(
            $configKey,
            $fileConfig['exclusions'],
            $fileConfig['inclusions'],
            $entityAliasResolverServiceId
        );

        return [
            $configBagServiceId,
            $entityAliasResolverServiceId,
            $exclusionProviderServiceId,
            $entityOverrideProviderServiceId
        ];
    }

    /**
     * @param string   $configKey
     * @param string[] $fileNames
     * @param array    $fileConfigs
     *
     * @return string[] [config bag service id, entity alias resolver service id, exclusion provider service id,
     *                  entity override provider service id]
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    private function configureMultiFileApi(string $configKey, array $fileNames, array $fileConfigs): array
    {
        $allAliases = [];
        $allSubstitutions = [];
        $allExclusions = [];
        $allInclusions = [];
        foreach ($fileNames as $fileName) {
            $fileConfig = $fileConfigs[$fileName];
            foreach ($fileConfig['aliases'] as $entityClass => $alias) {
                if (!isset($allAliases[$entityClass])) {
                    $allAliases[$entityClass] = $alias;
                }
            }
            foreach ($fileConfig['substitutions'] as $overriddenEntityClass => $entityClass) {
                if (!isset($allSubstitutions[$overriddenEntityClass])) {
                    $allSubstitutions[$overriddenEntityClass] = $entityClass;
                }
            }
            foreach ($fileConfig['exclusions'] as $exclusion) {
                if (!$this->hasInclusionOrExclusion($exclusion, $allExclusions)) {
                    $allExclusions[] = $exclusion;
                }
            }
            foreach ($fileConfig['inclusions'] as $inclusion) {
                if (!$this->hasInclusionOrExclusion($inclusion, $allInclusions)) {
                    $allInclusions[] = $inclusion;
                }
            }
        }

        $firstFileName = null;
        foreach ($fileNames as $key => $fileName) {
            if (!isset($fileConfigs[$fileName]['configKey'])) {
                $firstFileName = $fileName;
                break;
            }
        }

        foreach ($allSubstitutions as $overriddenEntityClass => $entityClass) {
            $allAliases[$overriddenEntityClass] = [];
            if ($firstFileName && !$this->hasEntityConfig($overriddenEntityClass, $fileNames, $fileConfigs)) {
                $fileConfigs[$firstFileName]['config']['entities'][$overriddenEntityClass] = [];
            }
        }

        $allConfigBags = [];
        foreach ($fileNames as $key => $fileName) {
            if (isset($fileConfigs[$fileName]['configKey'])) {
                $serviceId = $this->buildConfigBagServiceId($fileConfigs[$fileName]['configKey']);
            } else {
                $serviceId = $this->configureConfigBag(
                    sprintf('%s_%s_internal', $configKey, $key),
                    $fileConfigs[$fileName]['config']
                );
                $this->container->getDefinition($serviceId)->setPublic(false);
            }
            $allConfigBags[] = new Reference($serviceId);
        }

        $configBagServiceId = $this->configureCombinedConfigBag($configKey, $allConfigBags);
        $entityOverrideProviderServiceId = $this->configureEntityOverrideProvider(
            $configKey,
            $allSubstitutions
        );
        $entityAliasResolverServiceId = $this->configureEntityAliasResolver(
            $configKey,
            $allAliases,
            array_unique(array_column($allExclusions, 'entity')),
            $entityOverrideProviderServiceId
        );
        $exclusionProviderServiceId = $this->configureExclusionProvider(
            $configKey,
            $allExclusions,
            $allInclusions,
            $entityAliasResolverServiceId
        );

        return [
            $configBagServiceId,
            $entityAliasResolverServiceId,
            $exclusionProviderServiceId,
            $entityOverrideProviderServiceId
        ];
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
            if (isset($fileConfigs[$fileName]['config']['entities'][$entityClass])) {
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
                if (!array_key_exists($key, $existingItem)
                    || $existingItem[$key] !== $value
                ) {
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

    /**
     * @param string $configKey
     *
     * @return string
     */
    private function buildConfigBagServiceId(string $configKey): string
    {
        return 'oro_api.config_bag.' . $configKey;
    }

    /**
     * @param string $configKey
     * @param array  $config
     *
     * @return string config bag service id
     */
    private function configureConfigBag(string $configKey, array $config): string
    {
        $configBagServiceId = $this->buildConfigBagServiceId($configKey);
        $this->container
            ->register($configBagServiceId, ConfigBag::class)
            ->setArguments([$config])
            ->setPublic(true);

        return $configBagServiceId;
    }

    /**
     * @param string      $configKey
     * @param Reference[] $configBags
     *
     * @return string combined config bag service id
     */
    private function configureCombinedConfigBag(string $configKey, array $configBags): string
    {
        $configBagServiceId = 'oro_api.config_bag.' . $configKey;
        $this->container
            ->register($configBagServiceId, CombinedConfigBag::class)
            ->setArguments([
                $configBags,
                new Reference('oro_api.config_merger.entity'),
                new Reference('oro_api.config_merger.relation')
            ])
            ->setPublic(true);

        return $configBagServiceId;
    }

    /**
     * @param string $configKey
     * @param array  $aliases
     * @param array  $exclusions
     * @param string $entityOverrideProviderServiceId
     *
     * @return string entity alias resolver service id
     */
    private function configureEntityAliasResolver(
        string $configKey,
        array $aliases,
        array $exclusions,
        string $entityOverrideProviderServiceId
    ): string {
        $cacheServiceId = 'oro_api.entity_alias_cache.' . $configKey;
        $this->container
            ->setDefinition($cacheServiceId, new ChildDefinition('oro.cache.abstract'))
            ->setPublic(false)
            ->addMethodCall('setNamespace', ['oro_api_aliases_' . $configKey]);

        $providerServiceId = 'oro_api.entity_alias_provider.' . $configKey;
        $this->container
            ->register($providerServiceId, EntityAliasProvider::class)
            ->setArguments([$aliases, $exclusions])
            ->setPublic(false);

        $loaderServiceId = 'oro_api.entity_alias_loader.' . $configKey;
        $this->container
            ->register($loaderServiceId, EntityAliasLoader::class)
            ->setArguments([new Reference($entityOverrideProviderServiceId)])
            ->setPublic(false)
            ->addMethodCall('addEntityAliasProvider', [new Reference($providerServiceId)])
            ->addMethodCall('addEntityClassProvider', [new Reference($providerServiceId)]);

        $entityAliasResolverServiceId = 'oro_api.entity_alias_resolver.' . $configKey;
        $this->container
            ->register($entityAliasResolverServiceId, EntityAliasResolver::class)
            ->setArguments([
                new Reference($loaderServiceId),
                new Reference($entityOverrideProviderServiceId),
                new Reference($cacheServiceId),
                new Reference('logger'),
                $this->container->getParameter('kernel.debug')
            ])
            ->setPublic(true)
            ->addTag('monolog.logger', ['channel' => 'api']);

        return $entityAliasResolverServiceId;
    }

    /**
     * @param string $configKey
     * @param array  $substitutions
     *
     * @return string entity override provider service id
     */
    private function configureEntityOverrideProvider(string $configKey, array $substitutions): string
    {
        $entityOverrideProviderServiceId = 'oro_api.entity_override_provider.' . $configKey;
        $this->container
            ->register($entityOverrideProviderServiceId, EntityOverrideProvider::class)
            ->setArguments([$substitutions])
            ->setPublic(true);

        return $entityOverrideProviderServiceId;
    }

    /**
     * @param string $configKey
     * @param array  $exclusions
     * @param array  $inclusions
     * @param string $entityAliasResolverServiceId
     *
     * @return string
     */
    private function configureExclusionProvider(
        string $configKey,
        array $exclusions,
        array $inclusions,
        string $entityAliasResolverServiceId
    ): string {
        $exclusionProviderServiceId = 'oro_api.config_entity_exclusion_provider.' . $configKey;
        $this->container
            ->register($exclusionProviderServiceId, ConfigExclusionProvider::class)
            ->setArguments([
                new Reference('oro_entity.entity_hierarchy_provider.all'),
                $exclusions,
                $inclusions
            ])
            ->setPublic(false);

        $aliasedExclusionProviderServiceId = 'oro_api.aliased_entity_exclusion_provider.' . $configKey;
        $this->container
            ->register($aliasedExclusionProviderServiceId, AliasedEntityExclusionProvider::class)
            ->setArguments([new Reference($entityAliasResolverServiceId)])
            ->setPublic(false);

        $chainExclusionProviderServiceId = 'oro_api.chain_entity_exclusion_provider.' . $configKey;
        $this->container
            ->register($chainExclusionProviderServiceId, ChainExclusionProvider::class)
            ->setPublic(true)
            ->addMethodCall('addProvider', [new Reference($exclusionProviderServiceId)])
            ->addMethodCall('addProvider', [new Reference($aliasedExclusionProviderServiceId)])
            ->addMethodCall('addProvider', [new Reference(self::SHARED_ENTITY_EXCLUSION_PROVIDER_SERVICE_ID)]);

        return $chainExclusionProviderServiceId;
    }

    /**
     * @param array $items [[service id, expression], ...]
     *
     * @return array [[service id, expression], ...]
     */
    private function sortByRequestTypeExpression(array $items): array
    {
        ArrayUtil::sortBy(
            $items,
            true,
            function ($item) {
                $expression = $item[1];
                if (!$expression) {
                    return 0;
                }

                return substr_count($expression, '&') + 1;
            }
        );

        return $items;
    }
}
