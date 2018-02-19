<?php

namespace Oro\Bundle\ApiBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\DefinitionDecorator;
use Symfony\Component\DependencyInjection\Reference;

use Oro\Component\Config\Loader\CumulativeConfigLoader;
use Oro\Component\Config\Loader\YamlCumulativeFileLoader;
use Oro\Component\PhpUtils\ArrayUtil;
use Oro\Bundle\ApiBundle\Config\Definition\ApiConfiguration;
use Oro\Bundle\ApiBundle\Provider\CombinedConfigBag;
use Oro\Bundle\ApiBundle\Provider\ConfigBag;
use Oro\Bundle\ApiBundle\Provider\ConfigExclusionProvider;
use Oro\Bundle\ApiBundle\Provider\EntityAliasProvider;
use Oro\Bundle\EntityBundle\ORM\EntityAliasResolver;
use Oro\Bundle\EntityBundle\Provider\AliasedEntityExclusionProvider;
use Oro\Bundle\EntityBundle\Provider\ChainExclusionProvider;
use Oro\Bundle\EntityBundle\Provider\EntityAliasLoader;

/**
 * Configures services based on "config_files" section in the bundle configuration
 * and "entity_aliases", "exclusions" and "inclusions" sections
 * in "Resources/config/oro/api.yml" files, such as:
 * * configuration bags
 * * entity alias resolvers
 * * entity exclusion providers
 */
class ConfigurationLoader
{
    private const CONFIG_EXTENSION_REGISTRY_SERVICE_ID          = 'oro_api.config_extension_registry';
    private const CONFIG_BAG_REGISTRY_SERVICE_ID                = 'oro_api.config_bag_registry';
    private const ENTITY_ALIAS_RESOLVER_REGISTRY_SERVICE_ID     = 'oro_api.entity_alias_resolver_registry';
    private const ENTITY_EXCLUSION_PROVIDER_REGISTRY_SERVICE_ID = 'oro_api.entity_exclusion_provider_registry';
    private const SHARED_ENTITY_EXCLUSION_PROVIDER_SERVICE_ID   = 'oro_api.entity_exclusion_provider.shared';

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
        $fileConfigs = $this->loadConfigFiles($config);
        foreach ($config['config_files'] as $configKey => $fileConfig) {
            $serviceIds = $this->configureApi($configKey, $fileConfig['file_name'], $fileConfigs);
            $requestTypeExpression = $this->getRequestTypeExpression($fileConfig);

            $configBagsConfig[] = [$serviceIds[0], $requestTypeExpression];
            $entityAliasResolversConfig[] = [$serviceIds[1], $requestTypeExpression];
            $exclusionProvidersConfig[] = [$serviceIds[2], $requestTypeExpression];
        }
        $this->container->getDefinition(self::CONFIG_BAG_REGISTRY_SERVICE_ID)
            ->replaceArgument(0, $this->sortByRequestTypeExpression($configBagsConfig));
        $this->container->getDefinition(self::ENTITY_EXCLUSION_PROVIDER_REGISTRY_SERVICE_ID)
            ->replaceArgument(0, $this->sortByRequestTypeExpression($exclusionProvidersConfig));
        $this->container->getDefinition(self::ENTITY_ALIAS_RESOLVER_REGISTRY_SERVICE_ID)
            ->replaceArgument(0, $this->sortByRequestTypeExpression($entityAliasResolversConfig));
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

                    $entityAliases = $requestConfig[ApiConfiguration::ENTITY_ALIASES_SECTION];
                    unset($requestConfig[ApiConfiguration::ENTITY_ALIASES_SECTION]);
                    $exclusions = $requestConfig[ApiConfiguration::EXCLUSIONS_SECTION];
                    unset($requestConfig[ApiConfiguration::EXCLUSIONS_SECTION]);
                    $inclusions = $requestConfig[ApiConfiguration::INCLUSIONS_SECTION];
                    unset($requestConfig[ApiConfiguration::INCLUSIONS_SECTION]);

                    $fileConfigs[$fileName] = [
                        'config'        => $requestConfig,
                        'entityAliases' => $entityAliases,
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
     * @return array [config bag service id, entity alias resolver service id, exclusion provider service id]
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
     * @return array [config bag service id, entity alias resolver service id, exclusion provider service id]
     */
    private function configureSingleFileApi(string $configKey, array $fileConfig): array
    {
        $configBagServiceId = $this->configureConfigBag($configKey, $fileConfig['config']);
        $entityAliasResolverServiceId = $this->configureEntityAliasResolver(
            $configKey,
            $fileConfig['entityAliases'],
            array_column($fileConfig['exclusions'], 'entity')
        );
        $exclusionProviderServiceId = $this->configureExclusionProvider(
            $configKey,
            $fileConfig['exclusions'],
            $fileConfig['inclusions'],
            $entityAliasResolverServiceId
        );

        return [$configBagServiceId, $entityAliasResolverServiceId, $exclusionProviderServiceId];
    }

    /**
     * @param string   $configKey
     * @param string[] $fileNames
     * @param array    $fileConfigs
     *
     * @return array [config bag service id, entity alias resolver service id, exclusion provider service id]
     */
    private function configureMultiFileApi(string $configKey, array $fileNames, array $fileConfigs): array
    {
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
        $configBagServiceId = 'oro_api.config_bag.' . $configKey;
        $this->container->setDefinition(
            $configBagServiceId,
            new Definition(
                CombinedConfigBag::class,
                [
                    $allConfigBags,
                    new Reference('oro_api.config_merger.entity'),
                    new Reference('oro_api.config_merger.relation')
                ]
            )
        );

        $allEntityAliases = [];
        $allExclusions = [];
        $allInclusions = [];
        foreach ($fileNames as $fileName) {
            $fileConfig = $fileConfigs[$fileName];
            foreach ($fileConfig['entityAliases'] as $entityClass => $entityAlias) {
                if (!isset($allEntityAliases[$entityClass])) {
                    $allEntityAliases[$entityClass] = $entityAlias;
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
        $entityAliasResolverServiceId = $this->configureEntityAliasResolver(
            $configKey,
            $allEntityAliases,
            array_column($allExclusions, 'entity')
        );
        $exclusionProviderServiceId = $this->configureExclusionProvider(
            $configKey,
            $allExclusions,
            $allInclusions,
            $entityAliasResolverServiceId
        );

        return [$configBagServiceId, $entityAliasResolverServiceId, $exclusionProviderServiceId];
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
     * @return string
     */
    private function configureConfigBag(string $configKey, array $config): string
    {
        $configBagServiceId = $this->buildConfigBagServiceId($configKey);
        $this->container->setDefinition(
            $configBagServiceId,
            new Definition(ConfigBag::class, [$config])
        );

        return $configBagServiceId;
    }

    /**
     * @param string $configKey
     * @param array  $entityAliases
     * @param array  $exclusions
     *
     * @return string
     */
    private function configureEntityAliasResolver(string $configKey, array $entityAliases, array $exclusions): string
    {
        $cacheServiceId = 'oro_api.entity_alias_cache.' . $configKey;
        $cacheDef = $this->container->setDefinition(
            $cacheServiceId,
            new DefinitionDecorator('oro.cache.abstract')
        );
        $cacheDef->setPublic(false);
        $cacheDef->addMethodCall('setNamespace', ['oro_api_aliases_' . $configKey]);

        $providerServiceId = 'oro_api.entity_alias_provider.' . $configKey;
        $providerDef = $this->container->setDefinition(
            $providerServiceId,
            new Definition(EntityAliasProvider::class, [$entityAliases, $exclusions])
        );
        $providerDef->setPublic(false);

        $loaderServiceId = 'oro_api.entity_alias_loader.' . $configKey;
        $loaderDef = $this->container->setDefinition(
            $loaderServiceId,
            new Definition(EntityAliasLoader::class)
        );
        $loaderDef->setPublic(false);
        $loaderDef->addMethodCall('addEntityAliasProvider', [new Reference($providerServiceId)]);
        $loaderDef->addMethodCall('addEntityClassProvider', [new Reference($providerServiceId)]);

        $resolverServiceId = 'oro_api.entity_alias_resolver.' . $configKey;
        $resolverDef = $this->container->setDefinition(
            $resolverServiceId,
            new Definition(
                EntityAliasResolver::class,
                [
                    new Reference($loaderServiceId),
                    new Reference($cacheServiceId),
                    new Reference('logger'),
                    $this->container->getParameter('kernel.debug')
                ]
            )
        );
        $resolverDef->addTag('monolog.logger', ['channel' => 'api']);

        return $resolverServiceId;
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
        $exclusionProviderDef = $this->container->setDefinition(
            $exclusionProviderServiceId,
            new Definition(
                ConfigExclusionProvider::class,
                [
                    new Reference('oro_entity.entity_hierarchy_provider.all'),
                    $exclusions,
                    $inclusions
                ]
            )
        );
        $exclusionProviderDef->setPublic(false);

        $aliasedExclusionProviderServiceId = 'oro_api.aliased_entity_exclusion_provider.' . $configKey;
        $aliasedExclusionProviderDef = $this->container->setDefinition(
            $aliasedExclusionProviderServiceId,
            new Definition(AliasedEntityExclusionProvider::class, [new Reference($entityAliasResolverServiceId)])
        );
        $aliasedExclusionProviderDef->setPublic(false);

        $chainExclusionProviderServiceId = 'oro_api.chain_entity_exclusion_provider.' . $configKey;
        $chainExclusionProviderDef = $this->container->setDefinition(
            $chainExclusionProviderServiceId,
            new Definition(ChainExclusionProvider::class)
        );
        $chainExclusionProviderDef->addMethodCall(
            'addProvider',
            [new Reference($exclusionProviderServiceId)]
        );
        $chainExclusionProviderDef->addMethodCall(
            'addProvider',
            [new Reference($aliasedExclusionProviderServiceId)]
        );
        $chainExclusionProviderDef->addMethodCall(
            'addProvider',
            [new Reference(self::SHARED_ENTITY_EXCLUSION_PROVIDER_SERVICE_ID)]
        );

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
