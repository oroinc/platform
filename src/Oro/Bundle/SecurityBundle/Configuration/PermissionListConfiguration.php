<?php

namespace Oro\Bundle\SecurityBundle\Configuration;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\Processor;

class PermissionListConfiguration implements PermissionConfigurationInterface
{
    const ROOT_NODE_NAME = 'oro_permissions';
    const DEFAULT_GROUP_NAME = 'default';

    /**
     * {@inheritdoc}
     */
    public function processConfiguration(array $configs)
    {
        $processor = new Processor();

        return $processor->processConfiguration($this, $configs);
    }

    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $builder = new TreeBuilder();
        $root = $builder->root(static::ROOT_NODE_NAME);
        $root->useAttributeAsKey('name')
            ->beforeNormalization()
            ->always(
                function ($configs) {
                    foreach ($configs as $name => &$config) {
                        if (!isset($config['label'])) {
                            $config['label'] = $name;
                        }
                    }

                    return $configs;
                }
            )
            ->end()
            ->prototype('array')
                ->children()
                    ->scalarNode('label')
                    ->end()
                    ->scalarNode('description')
                    ->end()
                    ->arrayNode('group_names')
                        ->defaultValue([static::DEFAULT_GROUP_NAME])
                        ->beforeNormalization()
                        ->always(
                            function ($groupName) {
                                return array_unique(
                                    array_map(
                                        function ($value) {
                                            return $value === '' ? static::DEFAULT_GROUP_NAME : $value;
                                        },
                                        (array) $groupName
                                    )
                                );
                            }
                        )
                        ->end()
                        ->prototype('scalar')
                        ->end()
                    ->end()
                    ->booleanNode('apply_to_all')
                        ->defaultValue(true)
                    ->end()
                    ->arrayNode('apply_to_entities')
                        ->prototype('scalar')
                        ->end()
                    ->end()
                    ->arrayNode('exclude_entities')
                        ->prototype('scalar')
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $builder;
    }
}
