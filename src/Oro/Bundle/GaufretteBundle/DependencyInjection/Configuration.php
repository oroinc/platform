<?php

namespace Oro\Bundle\GaufretteBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $builder = new TreeBuilder('oro_gaufrette');
        $builder->getRootNode()->children()
            ->arrayNode('stream_wrapper')
                ->addDefaultsIfNotSet()
                ->children()
                    ->scalarNode('readonly_protocol')
                        ->info(
                            'The name of read-only Gaufrette protocol.'
                            . ' By default it is "{gaufrette protocol name}-readonly".'
                        )
                        ->defaultNull()
                    ->end()
                ->end()
            ->end()
        ->end();

        return $builder;
    }
}
