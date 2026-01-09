<?php

namespace Oro\Bundle\DataGridBundle\Extension\Sorter;

use Oro\Bundle\DataGridBundle\Extension\Formatter\Property\PropertyInterface;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Defines the configuration structure for datagrid sorting options.
 *
 * This configuration class validates and normalizes sorter settings including column sorters,
 * default sorting, multiple sorting support, and toolbar sorting controls.
 */
class Configuration implements ConfigurationInterface
{
    public const SORTERS_KEY                       = 'sorters';
    public const COLUMNS_KEY                       = 'columns';
    public const MULTISORT_KEY                     = 'multiple_sorting';
    public const DEFAULT_SORTERS_KEY               = 'default';
    public const TOOLBAR_SORTING_KEY               = 'toolbar_sorting';
    public const DISABLE_DEFAULT_SORTING_KEY       = 'disable_default_sorting';
    public const DISABLE_NOT_SELECTED_OPTION_KEY   = 'disable_not_selected_option';

    public const SORTERS_PATH                      = '[sorters]';
    public const COLUMNS_PATH                      = '[sorters][columns]';
    public const MULTISORT_PATH                    = '[sorters][multiple_sorting]';
    public const DEFAULT_SORTERS_PATH              = '[sorters][default]';
    public const TOOLBAR_SORTING_PATH              = '[sorters][toolbar_sorting]';
    public const DISABLE_DEFAULT_SORTING_PATH      = '[sorters][disable_default_sorting]';
    public const DISABLE_NOT_SELECTED_OPTION_PATH  = '[sorters][disable_not_selected_option]';

    #[\Override]
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $builder = new TreeBuilder(static::SORTERS_KEY);

        $builder->getRootNode()
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
                    ->booleanNode(static::DISABLE_NOT_SELECTED_OPTION_KEY)->defaultValue(false)->end()
                ->end()
            ->end();

        return $builder;
    }
}
