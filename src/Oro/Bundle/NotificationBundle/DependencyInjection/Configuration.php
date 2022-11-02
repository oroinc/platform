<?php

namespace Oro\Bundle\NotificationBundle\DependencyInjection;

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
        $treeBuilder = new TreeBuilder('oro_notification');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode->children()
            ->arrayNode('events')
            ->info('List of notification events.')
            ->prototype('scalar')->end();

        SettingsBuilder::append(
            $rootNode,
            [
                'email_notification_sender_email' => ['value' => sprintf('no-reply@%s.example', gethostname())],
                'email_notification_sender_name'  => ['value' => 'Oro'],
                'mass_notification_template'      => ['value' => 'system_maintenance'],
                'mass_notification_recipients'    => ['value' => '']
            ]
        );
        return $treeBuilder;
    }
}
