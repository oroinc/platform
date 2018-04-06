<?php

namespace Oro\Bundle\GoogleIntegrationBundle\DependencyInjection;

use Oro\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    /**
     * Bundle configuration structure
     *
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode    = $treeBuilder->root('oro_google_integration');

        SettingsBuilder::append($rootNode, [
            'client_id'     => [
                'value' => null,
                'type'  => 'text',
            ],
            'client_secret' => [
                'value' => null,
                'type'  => 'text',
            ],
            'google_api_key' => [
                'value' => null,
                'type'  => 'text',
            ]
        ]);

        return $treeBuilder;
    }
}
