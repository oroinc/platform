<?php

namespace Oro\Bundle\SecurityBundle\Configuration;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Provides schema for configuration that is loaded from "Resources/config/oro/permissions.yml" files.
 */
class PermissionConfiguration implements ConfigurationInterface
{
    public const ROOT_NODE          = 'oro_permissions';
    public const DEFAULT_GROUP_NAME = 'default';

    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $builder = new TreeBuilder(self::ROOT_NODE);
        $rootNode = $builder->getRootNode();

        $rootNode->useAttributeAsKey('name')
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
                        ->defaultValue([self::DEFAULT_GROUP_NAME])
                        ->beforeNormalization()
                        ->always(
                            function ($groupName) {
                                return array_unique(
                                    array_map(
                                        function ($value) {
                                            return $value === '' ? self::DEFAULT_GROUP_NAME : $value;
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
                    ->arrayNode('apply_to_interfaces')
                        ->prototype('scalar')
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $builder;
    }
}
