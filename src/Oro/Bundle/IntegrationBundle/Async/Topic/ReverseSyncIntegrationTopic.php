<?php

namespace Oro\Bundle\IntegrationBundle\Async\Topic;

use Oro\Component\MessageQueue\Topic\AbstractTopic;
use Oro\Component\MessageQueue\Topic\JobAwareTopicInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Topic for reverse integration syncing.
 */
class ReverseSyncIntegrationTopic extends AbstractTopic implements JobAwareTopicInterface
{
    public static function getName(): string
    {
        return 'oro.integration.revers_sync_integration';
    }

    public static function getDescription(): string
    {
        return 'Synchronizes an integration in reverse order.';
    }

    public function configureMessageBody(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefined([
                'integration_id',
                'connector',
                'connector_parameters',
            ])
            ->setRequired([
                'integration_id',
                'connector',
            ])
            ->setDefaults([
                'connector_parameters' => [],
            ])
            ->addAllowedTypes('integration_id', ['string', 'int'])
            ->addAllowedTypes('connector', ['null', 'string'])
            ->addAllowedTypes('connector_parameters', 'array');
    }

    public function createJobName($messageBody): string
    {
        return 'oro_integration:revers_sync_integration:' . $messageBody['integration_id'];
    }
}
