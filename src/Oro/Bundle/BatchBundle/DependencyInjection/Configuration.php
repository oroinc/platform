<?php

namespace Oro\Bundle\BatchBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Configuration
 *
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $treeBuilder->root('oro_batch')->children()
            ->scalarNode('cleanup_interval')
            ->defaultValue('1 month')
            ->cannotBeEmpty()
            ->example('1 month');

        return $treeBuilder;
    }
}
