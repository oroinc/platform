<?php

namespace Oro\Bundle\DataGridBundle\Extension\Toolbar;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Class Configuration for create datagrid toolbar configuration tree
 * @package Oro\Bundle\DataGridBundle\Extension\Toolbar
 */
class Configuration implements ConfigurationInterface
{
    /** @var int */
    private $defaultPerPage;

    /**
     * @param ConfigManager $cm
     */
    public function __construct(ConfigManager $cm)
    {
        $this->defaultPerPage = $cm->get('oro_data_grid.default_per_page');
    }

    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $builder = new TreeBuilder();

        $builder->root('toolbarOptions')
            ->children()
                ->booleanNode('hide')->defaultFalse()->end()
                ->booleanNode('addResetAction')->defaultTrue()->end()
                ->booleanNode('addRefreshAction')->defaultTrue()->end()
                ->booleanNode('addDatagridSettingsManager')->defaultTrue()->end()
                ->integerNode('turnOffToolbarRecordsNumber')->defaultValue(0)->end()
                ->arrayNode('pageSize')->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('hide')->defaultFalse()->end()
                        ->scalarNode('default_per_page')->defaultValue($this->defaultPerPage)->end()
                        ->arrayNode('items')
                            ->defaultValue([10, 25, 50, 100])
                            ->prototype('variable')->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('pagination')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('hide')->defaultFalse()->end()
                        ->booleanNode('onePage')->defaultFalse()->end()
                    ->end()
                ->end()
                ->arrayNode('placement')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('top')->defaultTrue()->end()
                        ->booleanNode('bottom')->defaultFalse()->end()
                    ->end()
                ->end()
                ->arrayNode('datagridSettings')
                    ->children()
                    ->scalarNode('minVisibleColumnsQuantity')->end()
                ->end()
            ->end();

        return $builder;
    }
}
