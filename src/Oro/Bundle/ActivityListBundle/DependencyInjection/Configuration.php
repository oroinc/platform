<?php

namespace Oro\Bundle\ActivityListBundle\DependencyInjection;

use Oro\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode    = $treeBuilder->root('oro_activity_list');

        SettingsBuilder::append(
            $rootNode,
            [
                'sorting_field'     => ['value' => 'updatedAt'],
                'sorting_direction' => ['value' => 'DESC'],
                'per_page'          => ['value' => 10],
                'grouping'          => ['value' => true],
            ]
        );

        return $treeBuilder;
    }
}
