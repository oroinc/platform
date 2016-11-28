<?php

namespace Oro\Bundle\IntegrationBundle\Manager;

use Oro\Bundle\IntegrationBundle\Async\Topics;
use Oro\Component\MessageQueue\Client\Message;
use Oro\Component\MessageQueue\Client\MessagePriority;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;

/**
 * This class is responsible for job scheduling.
 */
class GenuineSyncScheduler
{
    /** @var MessageProducerInterface */
    protected $producer;

    /**
     * @param MessageProducerInterface $producer
     */
    public function __construct(MessageProducerInterface $producer)
    {
        $this->producer = $producer;
    }

    /**
     * @param int $integrationId
     * @param string|null $connector
     * @param array $connectorParameters
     */
    public function schedule($integrationId, $connector = null, array $connectorParameters = [])
    {
        $this->producer->send(
            Topics::SYNC_INTEGRATION,
            new Message(
                [
                    'integration_id'       => $integrationId,
                    'connector_parameters' => $connectorParameters,
                    'connector'            => $connector,
                    'transport_batch_size' => 100,
                ],
                MessagePriority::VERY_LOW
            )
        );
    }
}
