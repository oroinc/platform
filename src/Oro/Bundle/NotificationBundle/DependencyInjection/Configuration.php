<?php

namespace Oro\Bundle\NotificationBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

use Oro\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see
 * {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode    = $treeBuilder->root('oro_notification');

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
