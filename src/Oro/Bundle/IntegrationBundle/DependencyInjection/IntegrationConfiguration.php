<?php

namespace Oro\Bundle\IntegrationBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;

class IntegrationConfiguration implements ConfigurationInterface
{
    const ROOT_NODE_NAME         = 'oro_integration';
    const SYNC_SETTING_ROOT_NODE = 'synchronization_settings';
    const FORM_NODE_NAME         = 'form';

    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $root        = $treeBuilder->root(self::ROOT_NODE_NAME);
        $root->children()
            ->arrayNode(self::FORM_NODE_NAME)
            ->children()->append($this->getSynchronizationSettingsTree())->end()
        ->end();

        return $treeBuilder;
    }

    /**
     * @return ArrayNodeDefinition
     */
    protected function getSynchronizationSettingsTree()
    {
        $builder = new TreeBuilder();

        $node = $builder->root(self::SYNC_SETTING_ROOT_NODE)
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
            ->end();

        return $node;
    }
}
