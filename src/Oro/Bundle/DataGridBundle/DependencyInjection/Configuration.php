<?php

namespace Oro\Bundle\DataGridBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

use Oro\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder;

class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode    = $treeBuilder->root('oro_data_grid');

        SettingsBuilder::append(
            $rootNode,
            [
                'default_per_page' => ['value' => 25],
                'full_screen_layout_enabled' => ['type' => 'boolean', 'value' => true],
            ]
        );

        return $treeBuilder;
    }
}
