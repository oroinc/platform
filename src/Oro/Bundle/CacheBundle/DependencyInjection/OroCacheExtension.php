<?php

namespace Oro\Bundle\CacheBundle\DependencyInjection;

use Oro\Component\Config\Loader\CumulativeConfigLoader;
use Oro\Component\Config\Loader\NullCumulativeFileLoader;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;
use Symfony\Component\Serializer\Mapping\Loader\LoaderChain;
use Symfony\Component\Serializer\Mapping\Loader\YamlFileLoader as SerializerYamlFileLoader;

class OroCacheExtension extends Extension
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yml');
        $loader->load('commands.yml');

        $this->loadMetadataFactoryDefinition($container);
    }

    private function loadMetadataFactoryDefinition(ContainerBuilder $container): void
    {
        $configLoader = new CumulativeConfigLoader(
            'oro_cache_attributes',
            new NullCumulativeFileLoader('Resources/config/oro/cache_metadata.yml')
        );
        $resources = $configLoader->load();
        $serializerFileLoaders = [];
        foreach ($resources as $resource) {
            $serializerFileLoaders[] = new Definition(SerializerYamlFileLoader::class, [$resource->path]);
        }
        $loader = new Definition(LoaderChain::class, [$serializerFileLoaders]);
        $serializerLoaders = [$loader];
        $definition = new Definition(ClassMetadataFactory::class, $serializerLoaders);
        $container->getDefinition('oro.cache.serializer.mapping.cache_warmer')->replaceArgument(0, $serializerLoaders);
        if ($container->getParameter('kernel.debug')) {
            $container->removeDefinition('oro.cache.serializer.mapping.cache_warmer');
        }
        $container->setDefinition('oro.cache.serializer.mapping.factory.class_metadata', $definition);
    }
}
