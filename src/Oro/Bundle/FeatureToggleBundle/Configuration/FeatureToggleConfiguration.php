<?php

namespace Oro\Bundle\FeatureToggleBundle\Configuration;

use Oro\Bundle\ActionBundle\Configuration\ConfigurationDefinitionInterface;

use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

class FeatureToggleConfiguration implements ConfigurationDefinitionInterface
{
    /**
     * @param array $configs
     * @return array
     */
    public function processConfiguration(array $configs)
    {
        $processor = new Processor();

        return $processor->processConfiguration($this, [$configs]);
    }
    
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $builder = new TreeBuilder();
        $root = $builder->root('features');

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
            ->scalarNode('strategy')
                ->defaultValue('unanimous')
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
