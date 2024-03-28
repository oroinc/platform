<?php

namespace Oro\Bundle\EmailBundle\DependencyInjection;

use Oro\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('oro_email');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->arrayNode('email_sync_exclusions')
                    ->info('Determines which email address owners should be excluded during synchronization.')
                    ->example(['Oro\Bundle\UserBundle\Entity\User'])
                    ->treatNullLike([])
                    ->prototype('scalar')->end()
                ->end()
                ->arrayNode('public_email_owners')
                    ->info('Determines which email address owners should be processed as public.')
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
                'feature_enabled' => ['value' => true],
                'signature' => ['value' => ''],
                'append_signature' => ['value' => true],
                'default_button_reply' => ['value' => true],
                'use_threads_in_emails' => ['value' => true],
                'minimum_input_length' => ['value' => 2],
                'show_recent_emails_in_user_bar' => ['value' => true],
                'attachment_sync_enable' => ['value' => true],
                'attachment_sync_max_size' => ['value' => 50],
                'attachment_max_size' => ['value' => 10],
                'attachment_preview_limit' => ['value' => 8],
                'sanitize_html' => ['value' => false],
                'email_template_wysiwyg_enabled' => ['value' => false],
                'threads_grouping' => ['value' => true],
                'smtp_settings_host' => ['value' => ''],
                'smtp_settings_port' => ['value' => null, 'type' => 'integer'],
                'smtp_settings_encryption' => ['value' => ''],
                'smtp_settings_username' => ['value' => ''],
                'smtp_settings_password' => ['value' => ''],
                'default_email_owner' => ['type' => 'integer', 'value' => null],
            ]
        );

        return $treeBuilder;
    }
}
