<?php

namespace Oro\Bundle\IntegrationBundle\Async\Topic;

use Oro\Component\MessageQueue\Topic\AbstractTopic;
use Oro\Component\MessageQueue\Topic\JobAwareTopicInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Topic for integration syncing.
 */
class SyncIntegrationTopic extends AbstractTopic implements JobAwareTopicInterface
{
    private int $transportBatchSize;

    public function __construct(int $transportBatchSize = 100)
    {
        $this->transportBatchSize = $transportBatchSize;
    }

    #[\Override]
    public static function getName(): string
    {
        return 'oro.integration.sync_integration';
    }

    #[\Override]
    public static function getDescription(): string
    {
        return 'Synchronizes an integration';
    }

    #[\Override]
    public function configureMessageBody(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefined([
                'integration_id',
                'connector',
                'connector_parameters',
                'transport_batch_size'
            ])
            ->setRequired([
                'integration_id',
            ])
            ->setDefaults([
                'connector' => null,
                'connector_parameters' => [],
                'transport_batch_size' => $this->transportBatchSize,
            ])
            ->addAllowedTypes('integration_id', ['string', 'int'])
            ->addAllowedTypes('connector', ['null', 'string'])
            ->addAllowedTypes('connector_parameters', 'array')
            ->addAllowedTypes('transport_batch_size', 'int');
    }

    #[\Override]
    public function createJobName($messageBody): string
    {
        return 'oro_integration:sync_integration:' . $messageBody['integration_id'];
    }
}
