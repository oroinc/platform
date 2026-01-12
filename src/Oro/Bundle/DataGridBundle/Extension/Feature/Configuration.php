<?php

namespace Oro\Bundle\DataGridBundle\Extension\Feature;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Defines the configuration structure for datagrid feature toggles.
 *
 * This configuration class validates and normalizes feature-related settings that control
 * datagrid functionality based on enabled features in the system.
 */
class Configuration implements ConfigurationInterface
{
    #[\Override]
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $builder = new TreeBuilder('features');

        $builder->getRootNode()
            ->children()
                ->scalarNode('entity_class_name_path')->end()
            ->end();

        return $builder;
    }
}
