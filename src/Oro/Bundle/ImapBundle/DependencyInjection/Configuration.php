<?php

namespace Oro\Bundle\ImapBundle\DependencyInjection;

use Oro\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder;
use Oro\Bundle\ImapBundle\Mailer\Transport\UserEmailOriginTransportFactory;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('oro_imap');
        $rootNode = $treeBuilder->getRootNode();
        $rootNode
            ->addDefaultsIfNotSet()
            ->children()
                ->scalarNode('user_email_origin_transport')
                    ->info(
                        sprintf(
                            'Name of the mailer transport to use for sending emails on behalf of a user. '
                            . 'Make sure it is present in "framework.mailer.transports" configuration section '
                            . 'with DSN "%s"',
                            UserEmailOriginTransportFactory::DSN
                        )
                    )
                    ->defaultValue('oro_user_email_origin')
                ->end()
            ->end();

        SettingsBuilder::append($rootNode, [
            'enable_google_imap' => [
                'value' => false,
                'type' => 'boolean',
            ],
            'enable_microsoft_imap' => [
                'value' => false,
                'type' => 'boolean',
            ],
        ]);

        return $treeBuilder;
    }
}
