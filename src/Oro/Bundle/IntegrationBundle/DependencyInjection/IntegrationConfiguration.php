<?php

namespace Oro\Bundle\IntegrationBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class IntegrationConfiguration implements ConfigurationInterface
{
    const ROOT_NODE_NAME = 'oro_integration';
    const FORM_NODE_NAME = 'form';

    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $root        = $treeBuilder->root(self::ROOT_NODE_NAME);
        $root
            ->children()
            ->append($this->getFormTree())
        ->end();

        return $treeBuilder;
    }

    /**
     * @return ArrayNodeDefinition
     */
    protected function getFormTree()
    {
        $builder = new TreeBuilder();

        $node = $builder->root(self::FORM_NODE_NAME)
            ->useAttributeAsKey('name')
            ->prototype('array')
                ->useAttributeAsKey('name')
                ->prototype('array')
                    ->children()
                        ->scalarNode('type')->isRequired()->end()
                        ->arrayNode('options')
                            ->prototype('variable')->end()
                        ->end()
                        ->integerNode('priority')->end()
                        ->arrayNode('applicable')
                            ->beforeNormalization()
                                ->ifString()
                                ->then(
                                    function ($v) {
                                        return [$v];
                                    }
                                )
                            ->end()
                            ->prototype('scalar')
                                ->cannotBeEmpty()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $node;
    }
}
