<?php

namespace Oro\Bundle\UIBundle\DependencyInjection;

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
        $treeBuilder = new TreeBuilder('oro_ui');
        $rootNode = $treeBuilder->getRootNode();

        SettingsBuilder::append(
            $rootNode,
            [
                'organization_name'    => ['value' => 'ORO'],
                'application_url'      => ['value' => 'http://localhost'],
                'navbar_position'      => ['value' => 'left'],
                'quick_create_actions' => ['value' => 'current_page']
            ]
        );

        return $treeBuilder;
    }
}
