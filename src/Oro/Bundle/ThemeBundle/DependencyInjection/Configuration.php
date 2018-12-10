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
            ->beforeNormalization()
                ->ifTrue(function ($value) {
                    if (!isset($value['themes'])) {
                        return false;
                    }

                    foreach ($value['themes'] as $themeName => $value) {
                        if (false !== strpos($themeName, '-') && false === strpos($themeName, '_')) {
                            return true;
                        }
                    }
                    return false;
                })
                ->thenInvalid("Theme name should not contain only '-' special characters")
            ->end()
            ->children()
                ->arrayNode('themes')
                    ->useAttributeAsKey('name')
                    ->prototype('array')
                        ->children()
                            ->scalarNode('label')
                                ->cannotBeEmpty()
                            ->end()
                            ->scalarNode('logo')
                            ->end()
                            ->scalarNode('icon')
                            ->end()
                            ->scalarNode('screenshot')
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
