<?php

namespace Oro\Bundle\DataGridBundle\Extension\Appearance;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public const GRID_APPEARANCE_TYPE = 'grid';
    public const LABEL_KEY = 'label';
    public const ICON_KEY = 'icon';
    public const DEFAULT_PROCESSING_KEY = 'default_processing';

    #[\Override]
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $builder = new TreeBuilder('appearances');
        $builder->getRootNode()
            ->children()
                ->arrayNode(static::GRID_APPEARANCE_TYPE)
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode(static::LABEL_KEY)->defaultValue('oro.datagrid.appearance.grid')->end()
                        ->scalarNode(static::ICON_KEY)->defaultValue('fa-table')->end()
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
