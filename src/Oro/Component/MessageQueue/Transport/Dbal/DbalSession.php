<?php

namespace Oro\Component\MessageQueue\Transport\Dbal;

use Oro\Component\MessageQueue\Transport\ConnectionInterface;
use Oro\Component\MessageQueue\Transport\MessageConsumerInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\MessageProducerInterface;
use Oro\Component\MessageQueue\Transport\Queue;
use Oro\Component\MessageQueue\Transport\QueueInterface;

/**
 * A Session object for DBAL connection.
 */
class DbalSession implements DbalSessionInterface
{
    /**
     * @var DbalConnection
     */
    private $connection;

    public function __construct(DbalConnection $connection)
    {
        $this->connection = $connection;
    }

    #[\Override]
    public function createMessage(
        mixed $body = '',
        array $properties = [],
        array $headers = []
    ): MessageInterface {
        $message = new DbalMessage();
        $message->setBody($body);
        $message->setProperties($properties);
        $message->setHeaders($headers);

        return $message;
    }

    #[\Override]
    public function createQueue(string $name): QueueInterface
    {
        return new Queue($name);
    }

    #[\Override]
    public function createConsumer(QueueInterface $queue): MessageConsumerInterface
    {
        $consumer = new DbalMessageConsumer($this, $queue);

        $options = $this->connection->getOptions();
        if (isset($options['polling_interval'])) {
            $consumer->setPollingInterval($options['polling_interval']);
        }

        return $consumer;
    }

    #[\Override]
    public function createProducer(): MessageProducerInterface
    {
        return new DbalMessageProducer($this->connection);
    }

    #[\Override]
    public function close(): void
    {
    }

    #[\Override]
    public function getConnection(): ConnectionInterface
    {
        return $this->connection;
    }
}
