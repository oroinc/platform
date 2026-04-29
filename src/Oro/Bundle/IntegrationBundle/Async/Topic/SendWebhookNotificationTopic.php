<?php

namespace Oro\Bundle\IntegrationBundle\Async\Topic;

use Oro\Component\MessageQueue\Topic\AbstractTopic;
use Oro\Component\MessageQueue\Topic\JobAwareTopicInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Topic for sending webhook notifications to remote endpoints.
 */
class SendWebhookNotificationTopic extends AbstractTopic implements JobAwareTopicInterface
{
    public const NAME = 'oro_integration.webhook_notification';

    #[\Override]
    public static function getName(): string
    {
        return self::NAME;
    }

    #[\Override]
    public static function getDescription(): string
    {
        return 'Send webhook notification to remote endpoints';
    }

    #[\Override]
    public function configureMessageBody(OptionsResolver $resolver): void
    {
        $resolver
            ->define('topic')
            ->required()
            ->allowedTypes('string')
            ->info('Webhook topic identifier');

        $resolver
            ->define('event_data')
            ->required()
            ->allowedTypes('array')
            ->info('Serialized event data');

        $resolver
            ->define('timestamp')
            ->default(time())
            ->allowedTypes('int')
            ->info('Timestamp when the event occurred');

        $resolver
            ->define('entity_class')
            ->allowedTypes('string', 'null')
            ->info('Optional Entity class name for ACL purposes');

        $resolver
            ->define('entity_id')
            ->allowedTypes('int', 'string', 'null')
            ->info('Optional Entity ID for ACL purposes');

        $resolver->define('message_id')
            ->required()
            ->allowedTypes('string')
            ->info('Webhook message ID used for logs and to reduce the risk of message replays');
    }

    #[\Override]
    public function createJobName($messageBody): string
    {
        return sprintf(
            '%s:%s:%s',
            self::NAME,
            $messageBody['topic'],
            $messageBody['message_id']
        );
    }
}
