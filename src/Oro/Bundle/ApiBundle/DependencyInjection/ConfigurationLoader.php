<?php

namespace Oro\Bundle\ApiBundle\DependencyInjection;

use Oro\Bundle\ApiBundle\Provider\CombinedConfigBag;
use Oro\Bundle\ApiBundle\Provider\ConfigBag;
use Oro\Bundle\ApiBundle\Provider\ConfigCache;
use Oro\Bundle\ApiBundle\Provider\ConfigExclusionProvider;
use Oro\Bundle\ApiBundle\Provider\EntityAliasLoader;
use Oro\Bundle\ApiBundle\Provider\EntityAliasProvider;
use Oro\Bundle\ApiBundle\Provider\EntityAliasResolver;
use Oro\Bundle\ApiBundle\Provider\EntityOverrideProvider;
use Oro\Bundle\CacheBundle\DependencyInjection\Compiler\CacheConfigurationPass as CacheConfiguration;
use Oro\Bundle\EntityBundle\Provider\AliasedEntityExclusionProvider;
use Oro\Bundle\EntityBundle\Provider\ChainExclusionProvider;
use Oro\Component\PhpUtils\ArrayUtil;
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
 */
class ConfigurationLoader
{
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
        foreach ($config['config_files'] as $configKey => $fileConfig) {
            list(
                $configBagServiceId,
                $entityAliasResolverServiceId,
                $exclusionProviderServiceId,
                $entityOverrideProviderServiceId
                ) = $this->configureApi($configKey, $fileConfig['file_name']);
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
     *
     * @return string[] [config bag service id, entity alias resolver service id, exclusion provider service id,
     *                  entity override provider service id]
     */
    private function configureApi(string $configKey, array $fileNames): array
    {
        return count($fileNames) === 1
            ? $this->configureSingleFileApi($configKey, $fileNames[0])
            : $this->configureMultiFileApi($configKey, $fileNames);
    }

    /**
     * @param string $configKey
     * @param string $fileName
     *
     * @return string[] [config bag service id, entity alias resolver service id, exclusion provider service id,
     *                  entity override provider service id]
     */
    private function configureSingleFileApi(string $configKey, string $fileName): array
    {
        $configCacheServiceId = $this->configureConfigCache($configKey);
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
            [$fileName]
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
            $entityOverrideProviderServiceId
        ];
    }

    /**
     * @param string   $configKey
     * @param string[] $fileNames
     *
     * @return string[] [config bag service id, entity alias resolver service id, exclusion provider service id,
     *                  entity override provider service id]
     */
    private function configureMultiFileApi(string $configKey, array $fileNames): array
    {
        $configCacheServiceId = $this->configureConfigCache($configKey);

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
            $fileNames
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
            $entityOverrideProviderServiceId
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
                new Reference('oro_api.config_cache_factory'),
                new Reference('oro_api.config_cache_warmer')
            ])
            ->setPublic(true);

        return $configCacheServiceId;
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
     * @param string   $configKey
     * @param string   $configCacheServiceId
     * @param string   $entityOverrideProviderServiceId
     * @param string[] $configFiles
     *
     * @return string entity alias resolver service id
     */
    private function configureEntityAliasResolver(
        string $configKey,
        string $configCacheServiceId,
        string $entityOverrideProviderServiceId,
        array $configFiles
    ): string {
        $cacheServiceId = 'oro_api.entity_alias_cache.' . $configKey;
        $this->container
            ->setDefinition($cacheServiceId, new ChildDefinition(CacheConfiguration::DATA_CACHE_NO_MEMORY_SERVICE))
            ->setPublic(false)
            ->addMethodCall('setNamespace', ['oro_api_aliases_' . $configKey]);

        $providerServiceId = 'oro_api.entity_alias_provider.' . $configKey;
        $this->container
            ->register($providerServiceId, EntityAliasProvider::class)
            ->setArguments([new Reference($configCacheServiceId)])
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
                $configFiles
            ])
            ->setPublic(true)
            ->addTag('monolog.logger', ['channel' => 'api']);

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
            ->setPublic(true);

        return $entityOverrideProviderServiceId;
    }

    /**
     * @param string $configKey
     * @param string $configCacheServiceId
     * @param string $entityAliasResolverServiceId
     *
     * @return string
     */
    private function configureExclusionProvider(
        string $configKey,
        string $configCacheServiceId,
        string $entityAliasResolverServiceId
    ): string {
        $exclusionProviderServiceId = 'oro_api.config_entity_exclusion_provider.' . $configKey;
        $this->container
            ->register($exclusionProviderServiceId, ConfigExclusionProvider::class)
            ->setArguments([
                new Reference('oro_entity.entity_hierarchy_provider.all'),
                new Reference($configCacheServiceId)
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
