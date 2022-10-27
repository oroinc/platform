<?php

namespace Oro\Bundle\AttachmentBundle\DependencyInjection\Imagine\Factory;

use Liip\ImagineBundle\DependencyInjection\Factory\Resolver\ResolverFactoryInterface;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * The factory to configure a liip imagine cache resolver that enables cache resolution
 * using Gaufrette filesystem abstraction layer.
 */
class GaufretteResolverFactory implements ResolverFactoryInterface
{
    /**
     * {@inheritDoc}
     */
    public function create(ContainerBuilder $container, $resolverName, array $config)
    {
        $resolverDefinition = new ChildDefinition(
            'oro_attachment.liip_imagine.cache.resolver.prototype.gaufrette'
        );
        $resolverDefinition->replaceArgument(0, new Reference($config['file_manager_service']));
        $resolverDefinition->replaceArgument(2, $config['url_prefix']);
        $resolverDefinition->replaceArgument(3, $config['cache_prefix']);
        $resolverDefinition->addTag('liip_imagine.cache.resolver', ['resolver' => $resolverName]);

        $resolverId = 'liip_imagine.cache.resolver.' . $resolverName;
        $container->setDefinition($resolverId, $resolverDefinition);

        return $resolverId;
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return 'oro_gaufrette';
    }

    /**
     * {@inheritDoc}
     */
    public function addConfiguration(ArrayNodeDefinition $builder)
    {
        $builder
            ->children()
                ->scalarNode('file_manager_service')
                    ->isRequired()
                    ->cannotBeEmpty()
                ->end()
                ->scalarNode('url_prefix')
                    ->defaultValue('media/cache')
                    ->cannotBeEmpty()
                ->end()
                ->scalarNode('cache_prefix')
                    ->defaultValue('')
                ->end()
            ->end();
    }
}
