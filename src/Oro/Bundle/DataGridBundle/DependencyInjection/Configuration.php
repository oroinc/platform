<?php

namespace Oro\Bundle\DataGridBundle\DependencyInjection;

use Oro\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('oro_data_grid');
        $rootNode    = $treeBuilder->getRootNode();

        SettingsBuilder::append(
            $rootNode,
            [
                'default_per_page' => ['value' => 25],
                'full_screen_layout_enabled' => ['type' => 'boolean', 'value' => true],
                'row_link_enabled' => ['type' => 'boolean', 'value' => true],
            ]
        );

        return $treeBuilder;
    }
}
