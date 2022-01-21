<?php

namespace Oro\Bundle\CacheBundle\DependencyInjection\Compiler;

use Doctrine\Common\Cache\CacheProvider;
use Doctrine\Common\Cache\Psr6\DoctrineProvider;
use Oro\Bundle\CacheBundle\Adapter\ChainAdapter;
use Oro\Bundle\CacheBundle\Provider\MemoryCacheChain;
use Oro\Component\Config\Cache\ConfigCacheWarmer;
use Symfony\Component\Cache\Adapter\ChainAdapter as SymfonyChainAdapter;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Configures caches.
 */
class CacheConfigurationPass implements CompilerPassInterface
{
    /** this cache should be used to caching data which need to be shared between nodes in a web farm */
    public const DATA_CACHE_SERVICE = 'oro.cache.abstract';
    /** data cache manager service */
    public const MANAGER_SERVICE_KEY = 'oro_cache.oro_data_cache_manager';
    /** the base service for static configuration providers */
    public const STATIC_CONFIG_PROVIDER_SERVICE = 'oro.static_config_provider.abstract';
    public const DATA_CACHE_POOL = 'oro.data.cache';
    public const DATA_CACHE_POOL_WITHOUT_MEMORY_CACHE = 'oro.data.cache.without_memory_cache';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $this->ensureAbstractDataCacheExists($container);
        $this->configureDataCacheManagerAndStaticConfigCache($container);
        $this->configureClassForChainAdapter($container);
    }

    /**
     * Makes sure abstract service for data cache exists
     */
    private function ensureAbstractDataCacheExists(ContainerBuilder $container): void
    {
        $cacheProvider = $this->getCacheProvider($container, static::DATA_CACHE_SERVICE);

        $container->setDefinition(self::DATA_CACHE_SERVICE, self::getMemoryCacheChain($cacheProvider));
    }

    /**
     * Configures data cache manager
     */
    private function configureDataCacheManagerAndStaticConfigCache(ContainerBuilder $container): void
    {
        $managerDef  = $container->getDefinition(self::MANAGER_SERVICE_KEY);
        $definitions = $container->getDefinitions();
        foreach ($definitions as $serviceId => $def) {
            if (!$def instanceof ChildDefinition || $def->isAbstract()) {
                continue;
            }
            if ($def->getParent() === self::DATA_CACHE_SERVICE) {
                $managerDef->addMethodCall(
                    'registerCacheProvider',
                    [new Reference($serviceId)]
                );
            } elseif ($def->getParent() === self::STATIC_CONFIG_PROVIDER_SERVICE) {
                $this->registerStaticConfigWarmer($container, $serviceId);
            }
        }
    }

    private function registerStaticConfigWarmer(ContainerBuilder $container, string $providerServiceId): void
    {
        $warmerServiceId = $providerServiceId . '.warmer';
        if (!$container->hasDefinition($warmerServiceId)) {
            // use priority = 200 to add this warmer at the begin of the warmers chain
            // to prevent double warmup in case some Application cache depends on this cache
            $container->register($warmerServiceId, ConfigCacheWarmer::class)
                ->setPublic(false)
                ->setArguments([new Reference($providerServiceId)])
                ->addTag('kernel.cache_warmer', ['priority' => 200]);
        }
    }

    private function getCacheProvider(ContainerBuilder $container, string $cacheDefinitionId): Definition
    {
        if ($container->hasDefinition($cacheDefinitionId)) {
            $cacheDefinition = $container->getDefinition($cacheDefinitionId);
            if (null === $cacheDefinition->getClass() && $cacheDefinition->isAbstract()) {
                return $cacheDefinition;
            }
            if (!\in_array(CacheProvider::class, class_parents($cacheDefinition->getClass()), true)) {
                throw new \InvalidArgumentException(sprintf(
                    'Cache providers for `%s` should extend doctrine CacheProvider::class. `%s` given',
                    $cacheDefinitionId,
                    $cacheDefinition->getClass()
                ));
            }
        } else {
            $cacheDefinition = $this->getFilesystemCache();
        }

        return $cacheDefinition;
    }

    private function getFilesystemCache(): Definition
    {
        $cacheDefinition = new Definition(
            FilesystemAdapter::class,
            ['', 0, '%kernel.cache_dir%/oro_data']
        );
        $cacheDefinition->setAbstract(true);
        $doctrineProviderDefinition = new Definition(DoctrineProvider::class, [$cacheDefinition]);
        $doctrineProviderDefinition->setFactory([DoctrineProvider::class, 'wrap']);

        return $doctrineProviderDefinition;
    }

    private function configureClassForChainAdapter(ContainerBuilder $container): void
    {
        if ($container->hasDefinition(self::DATA_CACHE_POOL)) {
            $adapter = $container->getDefinition(self::DATA_CACHE_POOL);
            if (SymfonyChainAdapter::class === $adapter->getClass()) {
                $adapter->setClass(ChainAdapter::class);
            }
        }
    }

    public static function getMemoryCacheChain(Definition $cacheProvider): Definition
    {
        $definition = new Definition(MemoryCacheChain::class, [$cacheProvider]);
        $definition->setAbstract(true);

        return $definition;
    }
}
