<?php

namespace Oro\Bundle\DataGridBundle\Extension\Totals;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    const TOTALS_PATH          = '[totals]';
    const COLUMNS_PATH         = '[totals][columns]';

    const TOTALS_LABEL_KEY     = 'label';
    const TOTALS_SQL_EXPRESSION_KEY = 'expr';
    const TOTALS_FORMATTER_KEY      = 'formatter';

    const TOTALS_PER_PAGE_ROW_KEY   = 'per_page';
    const TOTALS_HIDE_IF_ONE_PAGE_KEY = 'hide_if_one_page';
    const TOTALS_EXTEND_KEY         = 'extends';


    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $builder = new TreeBuilder();
        $builder->root('totals')
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
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $builder;
    }
}
