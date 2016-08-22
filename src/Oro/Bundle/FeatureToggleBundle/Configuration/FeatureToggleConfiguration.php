<?php

namespace Oro\Bundle\FeatureToggleBundle\Configuration;

use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

class FeatureToggleConfiguration implements ConfigurationInterface
{
    const ROOT = 'features';

    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $builder = new TreeBuilder();
        $root = $builder->root(self::ROOT);

        $children = $root->useAttributeAsKey('name')->prototype('array')->children();

        $root->end();

        $children
            ->scalarNode('toggle')
                ->isRequired()
                ->cannotBeEmpty()
            ->end()
            ->scalarNode('label')
                ->isRequired()
                ->cannotBeEmpty()
            ->end()
            ->scalarNode('description')
            ->end()
            ->arrayNode('dependency')
                ->prototype('variable')
                ->end()
            ->end()
            ->arrayNode('route')
                ->prototype('variable')
                ->end()
            ->end()
            ->arrayNode('workflow')
                ->prototype('variable')
                ->end()
            ->end()
            ->arrayNode('operation')
                ->prototype('variable')
                ->end()
            ->end()
            ->arrayNode('process')
                ->prototype('variable')
                ->end()
            ->end()
            ->arrayNode('configuration')
                ->prototype('variable')
                ->end()
            ->end()
            ->arrayNode('api')
                ->prototype('variable')
                ->end()
            ->end()
        ->end();

        return $builder;
    }
}
