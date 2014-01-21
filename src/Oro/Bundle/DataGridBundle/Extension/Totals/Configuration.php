<?php

namespace Oro\Bundle\DataGridBundle\Extension\Totals;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    const TOTALS_PATH          = '[totals]';
    const COLUMNS_PATH         = '[totals][columns]';

    const TOTALS_LABEL_KEY     = 'label';
    const TOTALS_QUERY_KEY     = 'query';
    const TOTALS_FORMATTER     = 'formatter';

    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $builder = new TreeBuilder();
        $builder->root('totals')
            ->children()
                ->arrayNode('columns')
                    ->prototype('array')
                        ->children()
                            ->scalarNode(self::TOTALS_LABEL_KEY)
                                ->defaultFalse()
                                ->end()
                            ->scalarNode(self::TOTALS_QUERY_KEY)
                                ->defaultFalse()
                                ->end()
                            ->scalarNode(self::TOTALS_FORMATTER)
                                ->defaultFalse()
                                ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $builder;
    }
}
