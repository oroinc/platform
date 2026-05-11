<?php

namespace Oro\Bundle\IntegrationBundle\Async\Topic;

use Oro\Component\MessageQueue\Topic\AbstractTopic;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Topic for processing a single webhook notification endpoint (child job).
 */
class ProcessSingleWebhookNotificationTopic extends AbstractTopic
{
    public const NAME = 'oro_integration.process_single_webhook_notification';

    #[\Override]
    public static function getName(): string
    {
        return self::NAME;
    }

    #[\Override]
    public static function getDescription(): string
    {
        return 'Process single webhook notification endpoint';
    }

    #[\Override]
    public function configureMessageBody(OptionsResolver $resolver): void
    {
        $resolver->define('message_id')
            ->required()
            ->allowedTypes('string')
            ->info('Webhook message ID used for logs and to reduce the risk of message replays');

        $resolver
            ->define('webhook_id')
            ->required()
            ->allowedTypes('string')
            ->info('ID of the WebhookProducerSettings entity');

        $resolver
            ->define('event_data')
            ->required()
            ->allowedTypes('array')
            ->info('Serialized entity data');

        $resolver
            ->define('timestamp')
            ->default(time())
            ->allowedTypes('int')
            ->info('Timestamp when the event occurred');

        $resolver
            ->define('job_id')
            ->default(null)
            ->allowedTypes('int', 'null')
            ->info('Job ID for tracking');

        $resolver
            ->define('metadata')
            ->default([])
            ->allowedTypes('array')
            ->info('Webhook useful Metadata');
    }
}
