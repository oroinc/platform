<?php

namespace Oro\Bundle\ActivityBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('oro_activity');
        $rootNode = $treeBuilder->getRootNode();
        $rootNode
            ->children()
                ->arrayNode('api')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('activity_association_names')
                            ->info(
                                'The names that should be used for activity associations in API. Use this config when'
                                . ' automatically generated names are not correct or names for different activity'
                                . ' associations conflict each other.'
                            )
                            ->example(['Acme\AppBundle\Entity\TodoTask' => 'activityAcmeTodoTasks'])
                            ->useAttributeAsKey('name')
                            ->normalizeKeys(false)
                            ->prototype('scalar')->cannotBeEmpty()->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
