<?php

namespace Oro\Bundle\GoogleIntegrationBundle\DependencyInjection;

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
        $treeBuilder = new TreeBuilder('oro_google_integration');
        $rootNode = $treeBuilder->getRootNode();

        SettingsBuilder::append($rootNode, [
            'client_id'      => ['value' => null, 'type' => 'text'],
            'client_secret'  => ['value' => null, 'type' => 'text'],
            'google_api_key' => ['value' => null, 'type' => 'text'],
            'enable_sso'     => ['value' => false, 'type' => 'boolean'],
            'sso_domains'    => ['value' => [], 'type' => 'array']
        ]);

        return $treeBuilder;
    }
}
