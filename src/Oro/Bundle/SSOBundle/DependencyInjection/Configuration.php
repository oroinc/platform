<?php

namespace Oro\Bundle\SSOBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

use Oro\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder;

class Configuration implements ConfigurationInterface
{
    /**
     * Bundle configuration structure
     *
     * @return TreeBuilder
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode    = $treeBuilder->root('oro_sso');

        SettingsBuilder::append($rootNode, [
            'enable_google_sso' => [
                'value' => false,
                'type'  => 'boolean',
            ],
            'domains'           => [
                'value' => [],
                'type'  => 'array',
            ],
        ]);

        return $treeBuilder;
    }
}
