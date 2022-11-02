<?php

namespace Oro\Bundle\MessageQueueBundle\Test\Functional;

use Oro\Bundle\MessageQueueBundle\Test\MessageCollector as BaseMessageCollector;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;

/**
 * This class is intended to be used in functional tests and allows to get sent messages.
 */
class MessageCollector extends BaseMessageCollector
{
    private DriverMessageCollector $driverMessageCollector;

    public function __construct(
        MessageProducerInterface $messageProducer,
        DriverMessageCollector $driverMessageCollector
    ) {
        parent::__construct($messageProducer);

        $this->driverMessageCollector = $driverMessageCollector;
    }

    public function getSentMessages(): array
    {
        return array_values($this->driverMessageCollector->getSentMessages());
    }

    public function clearTopicMessages($topic): self
    {
        $this->driverMessageCollector->clearTopicMessages($topic);

        return $this;
    }

    public function clear(): self
    {
        $this->driverMessageCollector->clear();

        return $this;
    }
}
