<?php

namespace Oro\Bundle\DataGridBundle\Extension\Appearance;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    const GRID_APPEARANCE_TYPE = 'grid';
    const LABEL_KEY = 'label';
    const ICON_KEY = 'icon';
    const DEFAULT_PROCESSING_KEY = 'default_processing';

    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $builder = new TreeBuilder();
        $builder->root('appearances')
            ->children()
                ->arrayNode(static::GRID_APPEARANCE_TYPE)
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode(static::LABEL_KEY)->defaultValue('oro.datagrid.appearance.grid')->end()
                        ->scalarNode(static::ICON_KEY)->defaultValue('icon-table')->end()
                        ->scalarNode(static::DEFAULT_PROCESSING_KEY)->defaultValue(true)->end()
                    ->end()
                ->end()
                ->arrayNode('board')
                    ->prototype('variable')
                ->end()
            ->end()
        ->end();

        return $builder;
    }
}
