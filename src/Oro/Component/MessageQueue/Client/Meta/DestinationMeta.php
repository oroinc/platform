<?php

namespace Oro\Component\MessageQueue\Client\Meta;

/**
 * Holds meta information about destination: name on client and transport sides and subscribers (message processors).
 */
class DestinationMeta
{
    /**
     * @var string Destination (queue) name on client side.
     */
    private string $queueName;

    /**
     * @var string Destination (queue) name on transport side.
     */
    private string $transportQueueName;

    /**
     * @var string[] Message processors names.
     */
    private array $messageProcessors;

    /**
     * @param string $queueName Destination (queue) name on client side.
     * @param string $transportQueueName Destination (queue) name on transport side.
     * @param string[] $messageProcessors Message processors names.
     */
    public function __construct(string $queueName, string $transportQueueName, array $messageProcessors = [])
    {
        $this->queueName = $queueName;
        $this->transportQueueName = $transportQueueName;
        $this->messageProcessors = $messageProcessors;
    }

    public function getQueueName(): string
    {
        return $this->queueName;
    }

    public function getTransportQueueName(): string
    {
        return $this->transportQueueName;
    }

    public function getMessageProcessors(): array
    {
        return $this->messageProcessors;
    }
}
