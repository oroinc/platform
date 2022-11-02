<?php

namespace Oro\Bundle\MicrosoftIntegrationBundle\DependencyInjection;

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
        $treeBuilder = new TreeBuilder('oro_microsoft_integration');
        $rootNode = $treeBuilder->getRootNode();

        SettingsBuilder::append($rootNode, [
            'client_id'     => ['value' => null, 'type' => 'text'],
            'client_secret' => ['value' => null, 'type' => 'text'],
            'tenant'        => ['value' => null, 'type' => 'text']
        ]);

        return $treeBuilder;
    }
}
