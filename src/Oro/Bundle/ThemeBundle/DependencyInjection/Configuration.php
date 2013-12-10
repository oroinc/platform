<?php

namespace Oro\Bundle\ThemeBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('oro_theme');

        $rootNode
            ->children()
                ->arrayNode('themes')
                    ->useAttributeAsKey('name')
                    ->prototype('array')
                        ->children()
                            ->scalarNode('label')
                                ->cannotBeEmpty()
                            ->end()
                            ->scalarNode('logo')
                                ->cannotBeEmpty()
                            ->end()
                            ->scalarNode('icon')
                                ->cannotBeEmpty()
                            ->end()
                            ->scalarNode('screenshot')
                                ->cannotBeEmpty()
                            ->end()
                            ->arrayNode('styles')
                                ->isRequired()
                                ->requiresAtLeastOneElement()
                                ->beforeNormalization()
                                    ->always(
                                        function ($value) {
                                            if (is_string($value)) {
                                                return array($value);
                                            }
                                            return $value;
                                        }
                                    )
                                ->end()
                                ->validate()
                                    ->always(
                                        function ($value) {
                                            return array_unique($value);
                                        }
                                    )
                                ->end()
                                ->prototype('scalar')
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->scalarNode('active_theme')
                    ->cannotBeEmpty()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
