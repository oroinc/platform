<?php

namespace Oro\Bundle\ReportBundle\DependencyInjection;

use Oro\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your config files
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode    = $treeBuilder->root('oro_report');

        SettingsBuilder::append(
            $rootNode,
            [
                'display_sql_query' => ['value' => false, 'type' => 'boolean']
            ]
        );

        return $treeBuilder;
    }
}
