<?php

namespace Oro\Bundle\EntityBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('oro_entity');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode->children()
            ->arrayNode('entity_name_representation')
                ->info('The configuration of an entity\'s text representation.')
                ->example([
                    'Acme\AppBundle\Entity\User' => [
                        'full' => ['namePrefix', 'firstName', 'middleName', 'lastName', 'nameSuffix'],
                        'short' => ['firstName', 'lastName']
                    ],
                    'Acme\AppBundle\Entity\Organization' => [
                        'full' => ['name']
                    ]
                ])
                ->useAttributeAsKey('class')
                ->arrayPrototype()
                    ->arrayPrototype()
                        ->useAttributeAsKey('format')
                        ->cannotBeEmpty()
                        ->prototype('scalar')
                            ->cannotBeEmpty()
                        ->end()
                    ->end()
                ->end()
            ->end()
            ->integerNode('default_query_cache_lifetime')
                ->info('Default doctrine`s query cache lifetime')
                ->defaultNull()
                ->min(1)
            ->end();

        return $treeBuilder;
    }
}
