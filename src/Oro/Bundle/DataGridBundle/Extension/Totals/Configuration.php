<?php

namespace Oro\Bundle\DataGridBundle\Extension\Totals;

use Oro\Bundle\DataGridBundle\Extension\Formatter\Property\PropertyInterface;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Defines the configuration parameters recognized by DataGrid > Totals.
 */
class Configuration implements ConfigurationInterface
{
    public const TOTALS_PATH                 = '[totals]';
    public const COLUMNS_PATH                = '[totals][columns]';

    public const TOTALS_LABEL_KEY            = 'label';
    public const TOTALS_SQL_EXPRESSION_KEY   = 'expr';
    public const TOTALS_FORMATTER_KEY        = 'formatter';
    public const TOTALS_DIVISOR_KEY          = 'divisor';

    public const TOTALS_PER_PAGE_ROW_KEY     = 'per_page';
    public const TOTALS_HIDE_IF_ONE_PAGE_KEY = 'hide_if_one_page';
    public const TOTALS_DISABLED             = PropertyInterface::DISABLED_KEY;

    public const TOTALS_EXTEND_KEY           = 'extends';

    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $builder = new TreeBuilder('totals');
        $builder->getRootNode()
            ->useAttributeAsKey('rows')
            ->prototype('array')
                ->children()
                    ->scalarNode(self::TOTALS_PER_PAGE_ROW_KEY)
                        ->info('Determines whether totals should be calculated for current page data or all data')
                        ->defaultFalse()
                    ->end()
                    ->scalarNode(self::TOTALS_HIDE_IF_ONE_PAGE_KEY)
                        ->info(
                            'Determines whether this total row should not be visible '
                            .'or not if all a grid has only one page'
                        )
                        ->defaultFalse()
                    ->end()
                    ->booleanNode(self::TOTALS_DISABLED)
                        ->info(
                            'Determines whether this total row should be disabled'
                        )
                        ->defaultFalse()
                    ->end()
                    ->scalarNode(self::TOTALS_EXTEND_KEY)
                        ->info('A parent total configuration')
                    ->end()
                    ->arrayNode('columns')
                        ->prototype('array')
                            ->children()
                                ->scalarNode(self::TOTALS_LABEL_KEY)
                                    ->end()
                                ->scalarNode(self::TOTALS_SQL_EXPRESSION_KEY)
                                    ->end()
                                ->scalarNode(self::TOTALS_FORMATTER_KEY)
                                    ->defaultFalse()
                                    ->end()
                                ->scalarNode(self::TOTALS_DIVISOR_KEY)
                                    ->info('A divisor to divide the starting value by a number before rendering it.')
                                    ->end()
                             ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $builder;
    }
}
