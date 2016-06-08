<?php

namespace Oro\Bundle\EmailBundle\DependencyInjection;

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
        $rootNode    = $treeBuilder->root('oro_email');

        $rootNode
            ->children()
                ->arrayNode('email_sync_exclusions')
                    ->info('Determines which email address owners should be excluded during synchronization.')
                    ->example(['Oro\Bundle\UserBundle\Entity\User'])
                    ->treatNullLike([])
                    ->prototype('scalar')->end()
                ->end()
                ->arrayNode('flash_notification')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->integerNode('max_emails_display')
                            ->defaultValue(4)
                        ->end()
                    ->end()
                ->end()
            ->end()
        ->end();

        SettingsBuilder::append(
            $rootNode,
            [
                'signature' => ['value' => ''],
                'append_signature' => ['value' => true],
                'default_button_reply' => ['value' => true],
                'use_threads_in_emails' => ['value' => true],
                'minimum_input_length' => ['value' => 2],
                'show_recent_emails_in_user_bar' => ['value' => true],
                'attachment_sync_enable' => ['value' => true],
                'attachment_sync_max_size' => ['value' => 0],
                'attachment_preview_limit' => ['value' => 8]
            ]
        );

        return $treeBuilder;
    }
}
