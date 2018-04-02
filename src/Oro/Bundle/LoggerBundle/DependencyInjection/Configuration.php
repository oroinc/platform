<?php

namespace Oro\Bundle\LoggerBundle\DependencyInjection;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    const ROOT_NODE = 'oro_logger';
    const LOGS_LEVEL_KEY = 'detailed_logs_level';
    const LOGS_TIMESTAMP_KEY = 'detailed_logs_end_timestamp';
    const EMAIL_NOTIFICATION_RECIPIENTS = 'email_notification_recipients';
    const EMAIL_NOTIFICATION_SUBJECT = 'email_notification_subject';

    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();

        $rootNode = $treeBuilder->root(self::ROOT_NODE);

        SettingsBuilder::append($rootNode, [
            self::LOGS_LEVEL_KEY => [
                'type' => 'string',
                'value' => 'notice'
            ],
            self::LOGS_TIMESTAMP_KEY => [
                'type' => 'integer',
                'value' => null
            ],
            self::EMAIL_NOTIFICATION_RECIPIENTS => [
                'type' => 'string',
                'value' => ''
            ],
            self::EMAIL_NOTIFICATION_SUBJECT => [
                'type' => 'string',
                'value' => 'An Error Occurred!'
            ],
        ]);

        return $treeBuilder;
    }

    /**
     * @param string $key
     * @return string
     */
    public static function getFullConfigKey($key)
    {
        return self::ROOT_NODE . ConfigManager::SECTION_MODEL_SEPARATOR . $key;
    }
}
