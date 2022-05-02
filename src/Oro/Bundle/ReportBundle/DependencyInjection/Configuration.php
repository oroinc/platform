<?php

namespace Oro\Bundle\ReportBundle\DependencyInjection;

use Oro\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('oro_report');
        $rootNode = $treeBuilder->getRootNode();
        $rootNode
            ->children()
                ->arrayNode('dbal')
                    ->children()
                        ->scalarNode('connection')
                            ->info('The name of DBAL connection that should be used to execute report queries.')
                        ->end()
                        ->arrayNode('datagrid_prefixes')
                            ->info(
                                'The list of name prefixes for datagrids that are reports'
                                . ' and should use the DBAL connection configured in the "connection" option.'
                            )
                            ->example(['acme_report_'])
                            ->prototype('scalar')
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        SettingsBuilder::append(
            $rootNode,
            [
                'display_sql_query' => ['value' => false, 'type' => 'boolean']
            ]
        );

        return $treeBuilder;
    }
}
