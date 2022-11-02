<?php

namespace Oro\Bundle\SidebarBundle\DependencyInjection;

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
        $treeBuilder = new TreeBuilder('oro_sidebar');
        $rootNode = $treeBuilder->getRootNode();

        SettingsBuilder::append(
            $rootNode,
            [
                'sidebar_left_active'  => ['value' => false, 'type' => 'bool'],
                'sidebar_right_active' => ['value' => true, 'type' => 'bool']
            ]
        );

        return $treeBuilder;
    }
}
