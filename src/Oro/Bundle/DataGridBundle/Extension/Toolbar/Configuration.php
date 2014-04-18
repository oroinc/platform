<?php

namespace Oro\Bundle\DataGridBundle\Extension\Toolbar;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;

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
                    ->end()
                ->end()
            ->end();

        return $builder;
    }
}
