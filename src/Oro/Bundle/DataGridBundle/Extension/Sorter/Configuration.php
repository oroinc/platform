<?php

namespace Oro\Bundle\DataGridBundle\Extension\Sorter;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

use Oro\Bundle\DataGridBundle\Extension\Formatter\Property\PropertyInterface;

class Configuration implements ConfigurationInterface
{
    const SORTERS_KEY                   = 'sorters';
    const COLUMNS_KEY                   = 'columns';
    const MULTISORT_KEY                 = 'multiple_sorting';
    const DEFAULT_SORTERS_KEY           = 'default';
    const TOOLBAR_SORTING_KEY           = 'toolbar_sorting';
    const DISABLE_DEFAULT_SORTING_KEY   = 'disable_default_sorting';

    const SORTERS_PATH                  = '[sorters]';
    const COLUMNS_PATH                  = '[sorters][columns]';
    const MULTISORT_PATH                = '[sorters][multiple_sorting]';
    const DEFAULT_SORTERS_PATH          = '[sorters][default]';
    const TOOLBAR_SORTING_PATH          = '[sorters][toolbar_sorting]';
    const DISABLE_DEFAULT_SORTING_PATH  = '[sorters][disable_default_sorting]';

    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $builder = new TreeBuilder();

        $builder->root(static::SORTERS_KEY)
            ->children()
                ->arrayNode(static::COLUMNS_KEY)
                    ->prototype('array')
                        ->children()
                            ->scalarNode(PropertyInterface::DATA_NAME_KEY)->isRequired()->end()
                            ->booleanNode(PropertyInterface::DISABLED_KEY)->end()
                            ->scalarNode(PropertyInterface::TYPE_KEY)->end()
                            ->variableNode('apply_callback')->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode(static::DEFAULT_SORTERS_KEY)
                    ->prototype('enum')
                        ->values([
                            AbstractSorterExtension::DIRECTION_DESC,
                            AbstractSorterExtension::DIRECTION_ASC
                        ])->end()
                    ->end()
                    ->booleanNode(static::MULTISORT_KEY)->end()
                    ->booleanNode(static::TOOLBAR_SORTING_KEY)->end()
                    ->booleanNode(static::DISABLE_DEFAULT_SORTING_KEY)->end()
                ->end()
            ->end();

        return $builder;
    }
}
