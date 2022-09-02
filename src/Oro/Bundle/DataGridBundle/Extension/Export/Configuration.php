<?php

namespace Oro\Bundle\DataGridBundle\Extension\Export;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Configuration class for the datagrid export configuration.
 */
class Configuration implements ConfigurationInterface
{
    public const XLSX_MAX_EXPORT_RECORDS = 10000;
    public const OPTION_PAGE_SIZE = 'page_size';
    public const OPTION_EXPORT_BY_PAGES = 'export_by_pages';

    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $builder = new TreeBuilder('export');

        $builder->getRootNode()
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
                    ->integerNode(self::OPTION_PAGE_SIZE)
                    ->end()
                    ->booleanNode(self::OPTION_EXPORT_BY_PAGES)
                    ->end()
                ->end()
            ->end();

        return $builder;
    }
}
