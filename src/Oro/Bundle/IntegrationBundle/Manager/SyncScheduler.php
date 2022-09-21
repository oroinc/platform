<?php

namespace Oro\Bundle\IntegrationBundle\Manager;

use Oro\Bundle\IntegrationBundle\Async\Topic\ReverseSyncIntegrationTopic;
use Oro\Component\MessageQueue\Client\Message;
use Oro\Component\MessageQueue\Client\MessagePriority;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;

/**
 * This class is responsible for job scheduling needed for two-way data sync.
 */
class SyncScheduler
{
    /**
     * @var MessageProducerInterface
     */
    private $producer;

    public function __construct(MessageProducerInterface $producer)
    {
        $this->producer = $producer;
    }

    /**
     * Schedules backward sync job
     *
     * @param int         $integrationId
     * @param string      $connector
     * @param array       $connectorParameters
     */
    public function schedule($integrationId, $connector, array $connectorParameters = [])
    {
        $this->producer->send(
            ReverseSyncIntegrationTopic::getName(),
            new Message(
                [
                    'integration_id'       => $integrationId,
                    'connector_parameters' => $connectorParameters,
                    'connector'            => $connector,
                ],
                MessagePriority::VERY_LOW
            )
        );
    }
}
