<?php

namespace Oro\Bundle\DataGridBundle\Extension\Columns;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    const COLUMNS_PATH = 'columns';

    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $builder = new TreeBuilder();

        $builder->root('columns')
            ->prototype('array')
                ->children()
                    ->scalarNode('label')->end()
                    ->scalarNode('type')->end()
                    ->scalarNode('frontend_type')->end()
                    ->booleanNode('translatable')->end()
                    ->booleanNode('editable')->end()
                ->end()
            ->end();

        return $builder;
    }
}
