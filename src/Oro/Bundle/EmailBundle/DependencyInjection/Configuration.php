<?php

namespace Oro\Bundle\EmailBundle\DependencyInjection;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder;
use Oro\Bundle\EmailBundle\Form\Model\SmtpSettings;
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
    const KEY_SMTP_SETTINGS = 'smtp_settings';
    const KEY_SMTP_SETTINGS_HOST = self::KEY_SMTP_SETTINGS . '_host';
    const KEY_SMTP_SETTINGS_PORT = self::KEY_SMTP_SETTINGS . '_port';
    const KEY_SMTP_SETTINGS_ENC = self::KEY_SMTP_SETTINGS . '_encryption';
    const KEY_SMTP_SETTINGS_USER = self::KEY_SMTP_SETTINGS . '_username';
    const KEY_SMTP_SETTINGS_PASS = self::KEY_SMTP_SETTINGS . '_password';

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
                'feature_enabled' => ['value' => true],
                'signature' => ['value' => ''],
                'append_signature' => ['value' => true],
                'default_button_reply' => ['value' => true],
                'use_threads_in_emails' => ['value' => true],
                'minimum_input_length' => ['value' => 2],
                'show_recent_emails_in_user_bar' => ['value' => true],
                'attachment_sync_enable' => ['value' => true],
                'attachment_sync_max_size' => ['value' => 50],
                'attachment_preview_limit' => ['value' => 8],
                'sanitize_html' => ['value' => false],
                'threads_grouping' => ['value' => true],
                self::KEY_SMTP_SETTINGS_HOST => ['value' => ''],
                self::KEY_SMTP_SETTINGS_PORT => ['value' => null, 'type' => 'integer'],
                self::KEY_SMTP_SETTINGS_ENC => ['value' => ''],
                self::KEY_SMTP_SETTINGS_USER => ['value' => ''],
                self::KEY_SMTP_SETTINGS_PASS => ['value' => ''],
            ]
        );

        return $treeBuilder;
    }

    /**
     * @param string $name
     * @param string $separator
     *
     * @return string
     */
    public static function getConfigKeyByName($name, $separator = ConfigManager::SECTION_MODEL_SEPARATOR)
    {
        return sprintf('oro_email%s%s', $separator, $name);
    }
}
