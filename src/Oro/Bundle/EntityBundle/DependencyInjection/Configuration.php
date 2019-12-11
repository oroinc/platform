<?php

namespace Oro\Bundle\EntityBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('oro_entity');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode->children()
            ->integerNode('default_query_cache_lifetime')
                ->info('Default doctrine`s query cache lifetime')
                ->defaultNull()
                ->min(1)
            ->end();

        return $treeBuilder;
    }
}
