<?php

namespace Oro\Bundle\BatchBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('oro_batch');
        $treeBuilder->getRootNode()
            ->children()
                ->scalarNode('log_batch')
                    ->info('Enables/Disables writing of batch log files for each batch job in var/logs/batch directory')
                    ->defaultFalse()
                ->end()
                ->scalarNode('cleanup_interval')
                    ->defaultValue('1 week')
                    ->cannotBeEmpty()
                    ->info('Sets the time interval to keep the batch job records in the database')
                    ->example('1 month')
                ->end()
            ->end();

        return $treeBuilder;
    }
}
