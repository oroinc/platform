<?php

namespace Oro\Bundle\ImapBundle\DependencyInjection;

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
        $treeBuilder = new TreeBuilder('oro_imap');
        $rootNode = $treeBuilder->getRootNode();

        SettingsBuilder::append($rootNode, [
            'enable_google_imap' => [
                'value' => false,
                'type' => 'boolean',
            ],
            'enable_microsoft_imap' => [
                'value' => false,
                'type' => 'boolean'
            ]
        ]);

        return $treeBuilder;
    }
}
