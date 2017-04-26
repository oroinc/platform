<?php

namespace Oro\Bundle\DataGridBundle\Extension\Feature;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $builder = new TreeBuilder();

        $builder->root('features')
            ->children()
                ->scalarNode('entity_class_name_path')->end()
            ->end();

        return $builder;
    }
}
