<?php

namespace Oro\Bundle\CacheBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\DefinitionDecorator;
use Symfony\Component\DependencyInjection\Reference;

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
        if (!$container->hasDefinition(self::FILE_CACHE_SERVICE)) {
            $definition = new Definition(
                'Oro\Bundle\CacheBundle\Provider\FilesystemCache',
                ['%kernel.cache_dir%/oro']
            );
            $definition->setAbstract(true);
            $container->setDefinition(self::FILE_CACHE_SERVICE, $definition);
        }
    }

    /**
     * Makes sure abstract service for data cache exists
     *
     * @param ContainerBuilder $container
     */
    protected function ensureAbstractDataCacheExists(ContainerBuilder $container)
    {
        if (!$container->hasDefinition(self::DATA_CACHE_SERVICE)) {
            $definition = new Definition(
                'Oro\Bundle\CacheBundle\Provider\FilesystemCache',
                ['%kernel.cache_dir%/oro_data']
            );
            $definition->setAbstract(true);
            $container->setDefinition(self::DATA_CACHE_SERVICE, $definition);
        }
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
}
