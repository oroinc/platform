<?php

namespace Oro\Bundle\DataGridBundle\Extension\Export;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    const XLSX_MAX_EXPORT_RECORDS = 10000;

    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $builder = new TreeBuilder();

        $builder->root('export')
            ->treatTrueLike(
                [
                    'csv' => [
                        'label' => 'oro.grid.export.csv'
                    ],
                    'xlsx' => [
                        'label' => 'oro.grid.export.xlsx',
                        'show_max_export_records_dialog' => true,
                        'max_export_records' => self::XLSX_MAX_EXPORT_RECORDS
                    ]
                ]
            )
            ->treatFalseLike([])
            ->treatNullLike([])
            ->useAttributeAsKey('name')
            ->prototype('array')
                ->children()
                    ->scalarNode('label')
                        ->cannotBeEmpty()
                    ->end()
                    ->booleanNode('show_max_export_records_dialog')
                    ->end()
                    ->integerNode('max_export_records')
                    ->end()
                ->end()
            ->end();

        return $builder;
    }
}
