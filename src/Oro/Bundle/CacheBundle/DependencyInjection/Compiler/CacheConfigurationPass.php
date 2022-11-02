<?php

namespace Oro\Bundle\CacheBundle\DependencyInjection\Compiler;

use Oro\Bundle\CacheBundle\Adapter\ChainAdapter;
use Oro\Component\Config\Cache\ConfigCacheWarmer;
use Symfony\Component\Cache\Adapter\ChainAdapter as SymfonyChainAdapter;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Configures caches.
 */
class CacheConfigurationPass implements CompilerPassInterface
{
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
        $this->configureDataCacheManagerAndStaticConfigCache($container);
        $this->configureClassForChainAdapter($container);
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
            if ($def->getParent() === self::DATA_CACHE_POOL) {
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

    private function configureClassForChainAdapter(ContainerBuilder $container): void
    {
        if ($container->hasDefinition(self::DATA_CACHE_POOL)) {
            $adapter = $container->getDefinition(self::DATA_CACHE_POOL);
            if (SymfonyChainAdapter::class === $adapter->getClass()) {
                $adapter->setClass(ChainAdapter::class);
            }
        }
    }
}
