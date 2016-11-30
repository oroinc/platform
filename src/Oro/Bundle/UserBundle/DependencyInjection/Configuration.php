<?php

namespace Oro\Bundle\UserBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

use Oro\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder;

class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $builder = new TreeBuilder();
        $rootNode = $builder->root('oro_user');

        $rootNode
            ->children()
                ->arrayNode('reset')
                    ->addDefaultsIfNotSet()
                    ->canBeUnset()
                    ->children()
                        ->scalarNode('ttl')
                            ->defaultValue(86400)
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('privileges')
                    ->useAttributeAsKey('name')
                    ->prototype('array')
                        ->addDefaultsIfNotSet()
                        ->children()
                            ->scalarNode('label')->end()
                            ->scalarNode('view_type')->end()
                            ->arrayNode('types')->prototype('scalar')->end()->end()
                            ->scalarNode('field_type')->end()
                            ->booleanNode('fix_values')->end()
                            ->scalarNode('default_value')->end()
                            ->booleanNode('show_default')->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        SettingsBuilder::append(
            $rootNode,
            [
                'password_min_length' => ['value' => 8, 'type' => 'scalar'],
                'password_lower_case' => ['value' => true, 'type' => 'boolean'],
                'password_upper_case' => ['value' => true, 'type' => 'boolean'],
                'password_numbers' => ['value' => true, 'type' => 'boolean'],
                'password_special_chars' => ['value' => false, 'type' => 'boolean'],
            ]
        );

        return $builder;
    }
}
