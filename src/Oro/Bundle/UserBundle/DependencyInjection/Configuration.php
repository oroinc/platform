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
                            ->arrayNode('types')->end()
                            ->scalarNode('field_type')->end()
                            ->booleanNode('fix_values')->end()
                            ->scalarNode('default_value')->end()
                            ->booleanNode('show_default')->end()
                        ->end()
                    ->end()
                    ->defaultValue(
                        array(
                            'entity'=>array(
                                'label' => 'oro.user.privileges.entity.label',
                                'view_type' => 'grid',
                                'types' => array('entity'),
                                'field_type' => 'oro_acl_access_level_selector',
                                'fix_values' => false,
                                'default_value' => 5,
                                'show_default' => true,
                            ),
                            'action'=>array(
                                'label' => 'oro.user.privileges.action.label',
                                'view_type' => 'list',
                                'types' => array('action'),
                                'field_type' => 'oro_acl_access_level_selector',
                                'fix_values' => false,
                                'default_value' => 1,
                                'show_default' => false,
                            )
                        )
                    )
                ->end()
            ->end();

        SettingsBuilder::append($rootNode, array(
            'enable_google_sso' => array(
                'value' => false,
                'type' => 'boolean',
            ),
            'google_sso_domains'=> array(
                'value' => [],
                'type' => 'array',
            ),
        ));

        return $builder;
    }
}
