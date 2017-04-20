<?php

namespace Oro\Bundle\CacheBundle\DependencyInjection\Compiler;

use Doctrine\Common\Cache\CacheProvider;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\DefinitionDecorator;
use Symfony\Component\DependencyInjection\Reference;

use Oro\Bundle\CacheBundle\Provider\MemoryCacheChain;
use Oro\Bundle\CacheBundle\Provider\FilesystemCache;

class CacheConfigurationPass implements CompilerPassInterface
{
    /** this cache should be used to caching data private for each node in a web farm */
    const FILE_CACHE_SERVICE = 'oro.file_cache.abstract';
    /** this cache should be used to caching data which need to be shared between nodes in a web farm */
    const DATA_CACHE_SERVICE = 'oro.cache.abstract';
    /** data cache manager service */
    const MANAGER_SERVICE_KEY = 'oro_cache.oro_data_cache_manager';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $this->ensureAbstractFileCacheExists($container);
        $this->ensureAbstractDataCacheExists($container);
        $this->configureDataCacheManager($container);
    }

    /**
     * Makes sure abstract service for file cache exists
     *
     * @param ContainerBuilder $container
     */
    protected function ensureAbstractFileCacheExists(ContainerBuilder $container)
    {
        $cacheProviders = $this->getCacheProviders($container, static::FILE_CACHE_SERVICE);

        $container->setDefinition(self::FILE_CACHE_SERVICE, $this->getMemoryCacheChain($cacheProviders));
    }

    /**
     * Makes sure abstract service for data cache exists
     *
     * @param ContainerBuilder $container
     */
    protected function ensureAbstractDataCacheExists(ContainerBuilder $container)
    {
        $cacheProviders = $this->getCacheProviders($container, static::DATA_CACHE_SERVICE);

        $container->setDefinition(self::DATA_CACHE_SERVICE, $this->getMemoryCacheChain($cacheProviders));
    }

    /**
     * Configures data cache manager
     *
     * @param ContainerBuilder $container
     */
    protected function configureDataCacheManager(ContainerBuilder $container)
    {
        if (!$container->hasDefinition(self::MANAGER_SERVICE_KEY)) {
            return;
        }

        $managerDef  = $container->getDefinition(self::MANAGER_SERVICE_KEY);
        $definitions = $container->getDefinitions();
        foreach ($definitions as $serviceId => $def) {
            if ($def instanceof DefinitionDecorator
                && !$def->isAbstract()
                && in_array($def->getParent(), [self::FILE_CACHE_SERVICE, self::DATA_CACHE_SERVICE])
            ) {
                $managerDef->addMethodCall(
                    'registerCacheProvider',
                    [new Reference($serviceId)]
                );
            }
        }
    }

    /**
     * @param ContainerBuilder $container
     * @param string           $cacheDefinitionId
     * @return array
     *
     * @throws \InvalidArgumentException
     */
    private function getCacheProviders(ContainerBuilder $container, $cacheDefinitionId)
    {
        $cacheProviders = [];
        if ($container->hasDefinition($cacheDefinitionId)) {
            $cacheDefinition = $container->getDefinition($cacheDefinitionId);

            if (!in_array(CacheProvider::class, class_parents($cacheDefinition->getClass()), true)) {
                throw new \InvalidArgumentException(sprintf(
                    'Cache providers for `%s` should extend doctrine CacheProvider::class. `%s` given',
                    $cacheDefinitionId,
                    $cacheDefinition->getClass()
                ));
            }

            $cacheProviders[] = $cacheDefinition;
        } else {
            $cacheProviders[] = $this->getFilesystemCache($cacheDefinitionId);
        }

        return $cacheProviders;
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
     * @param array $cacheProviders
     *
     * @return Definition
     */
    private function getMemoryCacheChain(array $cacheProviders)
    {
        $definition = new Definition(
            MemoryCacheChain::class,
            [$cacheProviders]
        );
        $definition->setAbstract(true);

        return $definition;
    }
}
