<?php

namespace Oro\Bundle\CacheBundle\DependencyInjection\Compiler;

use Doctrine\Common\Cache\CacheProvider;
use Oro\Bundle\CacheBundle\Provider\FilesystemCache;
use Oro\Bundle\CacheBundle\Provider\MemoryCacheChain;
use Oro\Component\Config\Cache\ConfigCacheWarmer;
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
    /** this cache should be used to caching data private for each node in a web farm */
    public const FILE_CACHE_SERVICE = 'oro.file_cache.abstract';
    /** this cache should be used to caching data which need to be shared between nodes in a web farm */
    public const DATA_CACHE_SERVICE = 'oro.cache.abstract';
    /** the same as "oro.cache.abstract" but without additional in-memory caching */
    public const DATA_CACHE_NO_MEMORY_SERVICE = 'oro.cache.abstract.without_memory_cache';
    /** data cache manager service */
    public const MANAGER_SERVICE_KEY = 'oro_cache.oro_data_cache_manager';
    /** the base service for static configuration providers */
    public const STATIC_CONFIG_PROVIDER_SERVICE = 'oro.static_config_provider.abstract';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $this->ensureAbstractFileCacheExists($container);
        $this->ensureAbstractDataCacheExists($container);
        $this->configureDataCacheManagerAndStaticConfigCache($container);
    }

    /**
     * Makes sure abstract service for file cache exists
     *
     * @param ContainerBuilder $container
     */
    private function ensureAbstractFileCacheExists(ContainerBuilder $container)
    {
        $cacheProvider = $this->getCacheProvider($container, static::FILE_CACHE_SERVICE);

        $container->setDefinition(self::FILE_CACHE_SERVICE, $this->getMemoryCacheChain($cacheProvider));
    }

    /**
     * Makes sure abstract service for data cache exists
     *
     * @param ContainerBuilder $container
     */
    private function ensureAbstractDataCacheExists(ContainerBuilder $container)
    {
        $cacheProvider = $this->getCacheProvider($container, static::DATA_CACHE_SERVICE);

        $container->setDefinition(self::DATA_CACHE_SERVICE, $this->getMemoryCacheChain($cacheProvider));
        $container->setDefinition(self::DATA_CACHE_NO_MEMORY_SERVICE, $cacheProvider);
    }

    /**
     * Configures data cache manager
     *
     * @param ContainerBuilder $container
     */
    private function configureDataCacheManagerAndStaticConfigCache(ContainerBuilder $container)
    {
        $parentServices = [
            self::FILE_CACHE_SERVICE,
            self::DATA_CACHE_SERVICE,
            self::DATA_CACHE_NO_MEMORY_SERVICE
        ];
        $managerDef  = $container->getDefinition(self::MANAGER_SERVICE_KEY);
        $definitions = $container->getDefinitions();
        foreach ($definitions as $serviceId => $def) {
            if (!$def instanceof ChildDefinition || $def->isAbstract()) {
                continue;
            }
            if (in_array($def->getParent(), $parentServices, true)) {
                $managerDef->addMethodCall(
                    'registerCacheProvider',
                    [new Reference($serviceId)]
                );
            } elseif ($def->getParent() === self::STATIC_CONFIG_PROVIDER_SERVICE) {
                $this->registerStaticConfigWarmer($container, $serviceId);
            }
        }
    }

    /**
     * @param ContainerBuilder $container
     * @param string           $providerServiceId
     */
    private function registerStaticConfigWarmer(ContainerBuilder $container, $providerServiceId)
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

    /**
     * @param ContainerBuilder $container
     * @param string           $cacheDefinitionId
     *
     * @return Definition
     *
     * @throws \InvalidArgumentException
     */
    private function getCacheProvider(ContainerBuilder $container, $cacheDefinitionId)
    {
        if ($container->hasDefinition($cacheDefinitionId)) {
            $cacheDefinition = $container->getDefinition($cacheDefinitionId);
            if (null === $cacheDefinition->getClass() && $cacheDefinition->isAbstract()) {
                return $cacheDefinition;
            }
            if (!in_array(CacheProvider::class, class_parents($cacheDefinition->getClass()), true)) {
                throw new \InvalidArgumentException(sprintf(
                    'Cache providers for `%s` should extend doctrine CacheProvider::class. `%s` given',
                    $cacheDefinitionId,
                    $cacheDefinition->getClass()
                ));
            }
        } else {
            $cacheDefinition = $this->getFilesystemCache($cacheDefinitionId);
        }

        return $cacheDefinition;
    }

    /**
     * @param string $cacheDefinitionId
     *
     * @return Definition
     */
    private function getFilesystemCache($cacheDefinitionId)
    {
        $path = $cacheDefinitionId === static::FILE_CACHE_SERVICE ? 'oro' : 'oro_data';

        $cacheDefinition = new Definition(
            FilesystemCache::class,
            [sprintf('%%kernel.cache_dir%%/%s', $path)]
        );
        $cacheDefinition->setAbstract(true);

        return $cacheDefinition;
    }

    /**
     * @param Definition $cacheProvider
     *
     * @return Definition
     */
    public static function getMemoryCacheChain(Definition $cacheProvider)
    {
        $definition = new Definition(MemoryCacheChain::class, [$cacheProvider]);
        $definition->setAbstract(true);

        return $definition;
    }
}
