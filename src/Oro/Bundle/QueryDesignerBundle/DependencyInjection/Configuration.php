<?php

namespace Oro\Bundle\QueryDesignerBundle\DependencyInjection;

use Oro\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    #[\Override]
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('oro_query_designer');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->arrayNode('collapsed_associations')
                    ->info(
                        'The configuration of entities whose associations can be used in the query designer'
                        . ' without expanding their fields.'
                    )
                    ->example([
                        'Acme\AppBundle\Entity\User' => [
                            'virtual_fields' => ['id'],
                            'search_fields' => ['firstName', 'lastName']
                        ]
                    ])
                    ->useAttributeAsKey('class')
                    ->arrayPrototype()
                        ->children()
                            ->arrayNode('virtual_fields')
                                ->performNoDeepMerging()
                                ->cannotBeEmpty()
                                ->prototype('scalar')->cannotBeEmpty()->end()
                            ->end()
                            ->arrayNode('search_fields')
                                ->performNoDeepMerging()
                                ->cannotBeEmpty()
                                ->prototype('scalar')->cannotBeEmpty()->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        SettingsBuilder::append(
            $rootNode,
            [
                'conditions_group_merge_same_entity_conditions' => ['type' => 'boolean', 'value' => true]
            ]
        );

        return $treeBuilder;
    }
}
