<?php

namespace Oro\Bundle\ApiBundle\DependencyInjection;

use Oro\Bundle\ApiBundle\Provider\CombinedConfigBag;
use Oro\Bundle\ApiBundle\Provider\ConfigBag;
use Oro\Bundle\ApiBundle\Provider\ConfigCache;
use Oro\Bundle\ApiBundle\Provider\ConfigCacheStateRegistry;
use Oro\Bundle\ApiBundle\Provider\ConfigExclusionProvider;
use Oro\Bundle\ApiBundle\Provider\EntityAliasLoader;
use Oro\Bundle\ApiBundle\Provider\EntityAliasProvider;
use Oro\Bundle\ApiBundle\Provider\EntityAliasResolver;
use Oro\Bundle\ApiBundle\Provider\EntityOverrideProvider;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\CacheBundle\DependencyInjection\Compiler\CacheConfigurationPass as CacheConfiguration;
use Oro\Bundle\EntityBundle\Provider\AliasedEntityExclusionProvider;
use Oro\Component\Config\Cache\ChainConfigCacheState;
use Oro\Component\PhpUtils\ArrayUtil;
use Symfony\Component\DependencyInjection\Argument\IteratorArgument;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

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
    private const CONFIG_BAG_REGISTRY_SERVICE_ID = 'oro_api.config_bag_registry';
    private const CONFIG_CACHE_STATE_REGISTRY_SERVICE_ID = 'oro_api.config_cache_state_registry';
    private const ENTITY_ALIAS_RESOLVER_REGISTRY_SERVICE_ID = 'oro_api.entity_alias_resolver_registry';
    private const ENTITY_EXCLUSION_PROVIDER_REGISTRY_SERVICE_ID = 'oro_api.entity_exclusion_provider_registry';
    private const SHARED_ENTITY_EXCLUSION_PROVIDER_SERVICE_ID = 'oro_api.entity_exclusion_provider.shared';
    private const ENTITY_OVERRIDE_PROVIDER_REGISTRY_SERVICE_ID = 'oro_api.entity_override_provider_registry';

    private ContainerBuilder $container;

    public function __construct(ContainerBuilder $container)
    {
        $this->container = $container;
    }

    public function load(array $config): void
    {
        if ($this->container->getParameter('kernel.debug')) {
            $this->container
                ->register(self::CONFIG_CACHE_STATE_REGISTRY_SERVICE_ID, ConfigCacheStateRegistry::class)
                ->setPublic(false)
                ->setArguments([[], new Reference('oro_api.request_expression_matcher')]);
            $this->container->getDefinition('oro_api.config_cache_factory')
                ->addMethodCall('addDependency', [new Reference('oro_entity.entity_configuration.provider')]);
        }

        $configBagsConfig = [];
        $exclusionProvidersConfig = [];
        $entityAliasResolversConfig = [];
        $entityOverrideProvidersConfig = [];
        $configCacheStatesConfig = [];
        foreach ($config['config_files'] as $configKey => $fileConfig) {
            [
                $configBagServiceId,
                $entityAliasResolverServiceId,
                $exclusionProviderServiceId,
                $entityOverrideProviderServiceId,
                $configCacheStateServiceId
            ] = $this->configureApi($configKey, $fileConfig['file_name']);
            $requestTypeExpression = $this->getRequestTypeExpression($fileConfig);

            $configBagsConfig[] = [$configBagServiceId, $requestTypeExpression];
            $entityAliasResolversConfig[] = [$entityAliasResolverServiceId, $requestTypeExpression];
            $exclusionProvidersConfig[] = [$exclusionProviderServiceId, $requestTypeExpression];
            $entityOverrideProvidersConfig[] = [$entityOverrideProviderServiceId, $requestTypeExpression];
            if ($configCacheStateServiceId) {
                $configCacheStatesConfig[] = [new Reference($configCacheStateServiceId), $requestTypeExpression];
            }
        }
        $this->container->getDefinition(self::CONFIG_BAG_REGISTRY_SERVICE_ID)
            ->setArgument('$configBags', $this->sortByRequestTypeExpression($configBagsConfig))
            ->setArgument('$container', $this->registerServiceLocator($configBagsConfig));
        $this->container->getDefinition(self::ENTITY_ALIAS_RESOLVER_REGISTRY_SERVICE_ID)
            ->setArgument('$entityAliasResolvers', $this->sortByRequestTypeExpression($entityAliasResolversConfig))
            ->setArgument('$container', $this->registerServiceLocator($entityAliasResolversConfig));
        $this->container->getDefinition(self::ENTITY_EXCLUSION_PROVIDER_REGISTRY_SERVICE_ID)
            ->setArgument('$exclusionProviders', $this->sortByRequestTypeExpression($exclusionProvidersConfig))
            ->setArgument('$container', $this->registerServiceLocator($exclusionProvidersConfig));
        $this->container->getDefinition(self::ENTITY_OVERRIDE_PROVIDER_REGISTRY_SERVICE_ID)
            ->setArgument(
                '$entityOverrideProviders',
                $this->sortByRequestTypeExpression($entityOverrideProvidersConfig)
            )
            ->setArgument('$container', $this->registerServiceLocator($entityOverrideProvidersConfig));
        if ($this->container->hasDefinition(self::CONFIG_CACHE_STATE_REGISTRY_SERVICE_ID)) {
            $this->container->getDefinition(self::CONFIG_CACHE_STATE_REGISTRY_SERVICE_ID)
                ->setArgument(0, $this->sortByRequestTypeExpression($configCacheStatesConfig));
            $this->container->getDefinition('oro_api.resources_cache_accessor')
                ->addMethodCall(
                    'setConfigCacheStateRegistry',
                    [new Reference(self::CONFIG_CACHE_STATE_REGISTRY_SERVICE_ID)]
                );
        }
    }

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
     *
     * @return string[] [config bag service id, entity alias resolver service id, exclusion provider service id,
     *                  entity override provider service id, config cache state service id]
     */
    private function configureApi(string $configKey, array $fileNames): array
    {
        return \count($fileNames) === 1
            ? $this->configureSingleFileApi($configKey, $fileNames[0])
            : $this->configureMultiFileApi($configKey, $fileNames);
    }

    /**
     * @param string $configKey
     * @param string $fileName
     *
     * @return string[] [config bag service id, entity alias resolver service id, exclusion provider service id,
     *                  entity override provider service id, config cache state service id]
     */
    private function configureSingleFileApi(string $configKey, string $fileName): array
    {
        $configCacheServiceId = $this->configureConfigCache($configKey);
        $configCacheStateServiceId = $this->configureConfigCacheState($configKey, $configCacheServiceId);
        $configBagServiceId = $this->configureConfigBag(
            $configKey,
            $fileName,
            $configCacheServiceId
        );
        $entityOverrideProviderServiceId = $this->configureEntityOverrideProvider(
            $configKey,
            $configCacheServiceId
        );
        $entityAliasResolverServiceId = $this->configureEntityAliasResolver(
            $configKey,
            $configCacheServiceId,
            $entityOverrideProviderServiceId,
            [$fileName],
            $configCacheStateServiceId
        );
        $exclusionProviderServiceId = $this->configureExclusionProvider(
            $configKey,
            $configCacheServiceId,
            $entityAliasResolverServiceId
        );

        return [
            $configBagServiceId,
            $entityAliasResolverServiceId,
            $exclusionProviderServiceId,
            $entityOverrideProviderServiceId,
            $configCacheStateServiceId
        ];
    }

    /**
     * @param string   $configKey
     * @param string[] $fileNames
     *
     * @return string[] [config bag service id, entity alias resolver service id, exclusion provider service id,
     *                  entity override provider service id, config cache state service id]
     */
    private function configureMultiFileApi(string $configKey, array $fileNames): array
    {
        $configCacheServiceId = $this->configureConfigCache($configKey);
        $configCacheStateServiceId = $this->configureConfigCacheState($configKey, $configCacheServiceId);

        $allConfigBags = [];
        foreach ($fileNames as $key => $fileName) {
            $serviceId = $this->configureConfigBag(
                sprintf('%s_%s_internal', $configKey, $key),
                $fileName,
                $configCacheServiceId
            );
            $this->container->getDefinition($serviceId)->setPublic(false);
            $allConfigBags[] = new Reference($serviceId);
        }

        $configBagServiceId = $this->configureCombinedConfigBag(
            $configKey,
            $allConfigBags
        );
        $entityOverrideProviderServiceId = $this->configureEntityOverrideProvider(
            $configKey,
            $configCacheServiceId
        );
        $entityAliasResolverServiceId = $this->configureEntityAliasResolver(
            $configKey,
            $configCacheServiceId,
            $entityOverrideProviderServiceId,
            $fileNames,
            $configCacheStateServiceId
        );
        $exclusionProviderServiceId = $this->configureExclusionProvider(
            $configKey,
            $configCacheServiceId,
            $entityAliasResolverServiceId
        );

        return [
            $configBagServiceId,
            $entityAliasResolverServiceId,
            $exclusionProviderServiceId,
            $entityOverrideProviderServiceId,
            $configCacheStateServiceId
        ];
    }

    /**
     * @param string $configKey
     *
     * @return string config cache service id
     */
    private function configureConfigCache(string $configKey): string
    {
        $configCacheServiceId = 'oro_api.config_cache.' . $configKey;
        $this->container
            ->register($configCacheServiceId, ConfigCache::class)
            ->setArguments([
                $configKey,
                '%kernel.debug%',
                new Reference('oro_api.config_cache_factory')
            ])
            ->setPublic(false);

        return $configCacheServiceId;
    }

    /**
     * @param string $configKey
     * @param string $configCacheServiceId
     *
     * @return string|null config cache state service id
     */
    private function configureConfigCacheState(string $configKey, string $configCacheServiceId): ?string
    {
        if (!$this->container->hasDefinition(self::CONFIG_CACHE_STATE_REGISTRY_SERVICE_ID)) {
            return null;
        }

        $configCacheStateServiceId = 'oro_api.config_cache_state.' . $configKey;
        $this->container
            ->register($configCacheStateServiceId, ChainConfigCacheState::class)
            ->setArguments([
                [new Reference($configCacheServiceId)]
            ])
            ->setPublic(false);

        return $configCacheStateServiceId;
    }

    /**
     * @param string $configKey
     * @param string $fileName
     * @param string $configCacheServiceId
     *
     * @return string config bag service id
     */
    private function configureConfigBag(string $configKey, string $fileName, string $configCacheServiceId): string
    {
        $configBagServiceId = 'oro_api.config_bag.' . $configKey;
        $this->container
            ->register($configBagServiceId, ConfigBag::class)
            ->setArguments([new Reference($configCacheServiceId), $fileName])
            ->setPublic(false);

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
                new Reference('oro_api.config_merger.entity')
            ])
            ->setPublic(false);

        return $configBagServiceId;
    }

    /**
     * @param string      $configKey
     * @param string      $configCacheServiceId
     * @param string      $entityOverrideProviderServiceId
     * @param string[]    $configFiles
     * @param string|null $configCacheStateServiceId
     *
     * @return string entity alias resolver service id
     */
    private function configureEntityAliasResolver(
        string $configKey,
        string $configCacheServiceId,
        string $entityOverrideProviderServiceId,
        array $configFiles,
        ?string $configCacheStateServiceId
    ): string {
        $cacheServiceId = 'oro_api.entity_alias_cache.' . $configKey;
        $this->container
            ->setDefinition(
                $cacheServiceId,
                new ChildDefinition(CacheConfiguration::DATA_CACHE_POOL_WITHOUT_MEMORY_CACHE)
            )
            ->setPublic(false)
            ->addTag('cache.pool', ['namespace' => 'oro_api_aliases_' . $configKey]);

        $providerServiceId = 'oro_api.entity_alias_provider.' . $configKey;
        $this->container
            ->register($providerServiceId, EntityAliasProvider::class)
            ->setArguments([new Reference($configCacheServiceId)])
            ->setPublic(false);

        $loaderServiceId = 'oro_api.entity_alias_loader.' . $configKey;
        $this->container
            ->register($loaderServiceId, EntityAliasLoader::class)
            ->setArguments([
                new IteratorArgument([new Reference($providerServiceId)]),
                new IteratorArgument([new Reference($providerServiceId)]),
                new Reference($entityOverrideProviderServiceId)
            ])
            ->setPublic(false);

        $entityAliasResolverServiceId = 'oro_api.entity_alias_resolver.' . $configKey;
        $entityAliasResolverDef = $this->container
            ->register($entityAliasResolverServiceId, EntityAliasResolver::class)
            ->setArguments([
                new Reference($loaderServiceId),
                new Reference($entityOverrideProviderServiceId),
                new Reference($cacheServiceId),
                new Reference('logger'),
                $configFiles
            ])
            ->setPublic(false)
            ->addTag('monolog.logger', ['channel' => 'api']);
        if ($configCacheStateServiceId) {
            $entityAliasResolverDef->addMethodCall(
                'setConfigCacheState',
                [new Reference($configCacheStateServiceId)]
            );
        }

        return $entityAliasResolverServiceId;
    }

    /**
     * @param string $configKey
     * @param string $configCacheServiceId
     *
     * @return string entity override provider service id
     */
    private function configureEntityOverrideProvider(string $configKey, string $configCacheServiceId): string
    {
        $entityOverrideProviderServiceId = 'oro_api.entity_override_provider.' . $configKey;
        $this->container
            ->register($entityOverrideProviderServiceId, EntityOverrideProvider::class)
            ->setArguments([new Reference($configCacheServiceId)])
            ->setPublic(false);

        return $entityOverrideProviderServiceId;
    }

    private function configureExclusionProvider(
        string $configKey,
        string $configCacheServiceId,
        string $entityAliasResolverServiceId
    ): string {
        $aliasedExclusionProviderServiceId = 'oro_api.aliased_entity_exclusion_provider.' . $configKey;
        $this->container
            ->register($aliasedExclusionProviderServiceId, AliasedEntityExclusionProvider::class)
            ->setArguments([new Reference($entityAliasResolverServiceId)])
            ->setPublic(false);

        $exclusionProviderServiceId = 'oro_api.config_entity_exclusion_provider.' . $configKey;
        $this->container
            ->register($exclusionProviderServiceId, ConfigExclusionProvider::class)
            ->setArguments([
                new Reference('oro_entity.entity_hierarchy_provider.all'),
                new Reference($configCacheServiceId)
            ])
            ->setPublic(false)
            ->addMethodCall('addProvider', [new Reference($aliasedExclusionProviderServiceId)])
            ->addMethodCall('addProvider', [new Reference(self::SHARED_ENTITY_EXCLUSION_PROVIDER_SERVICE_ID)]);

        return $exclusionProviderServiceId;
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
                $result = 0;
                $expression = $item[1];
                if ($expression) {
                    $result = 100;
                    $aspects = explode('&', $expression);
                    foreach ($aspects as $aspect) {
                        $result += $this->getRequestTypeAspectRank($aspect);
                    }
                }

                return $result;
            }
        );

        return $items;
    }

    private function getRequestTypeAspectRank(string $aspect): int
    {
        $rank = 8;
        if (strncmp($aspect, '!', 1) === 0) {
            $aspect = substr($aspect, 1);
            $rank /= 2;
        }
        if (RequestType::REST === $aspect || RequestType::JSON_API === $aspect) {
            $rank /= 2;
        }

        return $rank;
    }

    /**
     * @param array $items [[service id, expression], ...]
     *
     * @return Reference
     */
    private function registerServiceLocator(array $items): Reference
    {
        $services = [];
        foreach ($items as $item) {
            $id = $item[0];
            $services[$id] = new Reference($id);
        }

        return ServiceLocatorTagPass::register($this->container, $services);
    }
}
