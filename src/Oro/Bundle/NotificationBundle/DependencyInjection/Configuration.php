<?php

namespace Oro\Bundle\NotificationBundle\DependencyInjection;

use Oro\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your config files
 *
 * To learn more see
 * {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    const DEFAULT_MASS_NOTIFICATION_TEMPLATE = 'system_maintenance';

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
                'mass_notification_template'      => ['value' => self::DEFAULT_MASS_NOTIFICATION_TEMPLATE],
                'mass_notification_recipients'    => ['value' => '']
            ]
        );
        return $treeBuilder;
    }
}
