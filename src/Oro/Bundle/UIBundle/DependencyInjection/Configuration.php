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
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('oro_ui');

        SettingsBuilder::append(
            $rootNode,
            [
                'organization_name' => ['value' => 'ORO'],
                'application_url'   => ['value' => 'http://localhost'],
                'navbar_position'   => ['value' => 'left']
            ]
        );

        return $treeBuilder;
    }
}
