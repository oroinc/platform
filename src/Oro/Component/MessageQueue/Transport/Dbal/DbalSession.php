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

    /**
     * {@inheritdoc}
     */
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

    /**
     * {@inheritdoc}
     */
    public function createQueue(string $name): QueueInterface
    {
        return new Queue($name);
    }

    /**
     * {@inheritdoc}
     */
    public function createConsumer(QueueInterface $queue): MessageConsumerInterface
    {
        $consumer = new DbalMessageConsumer($this, $queue);

        $options = $this->connection->getOptions();
        if (isset($options['polling_interval'])) {
            $consumer->setPollingInterval($options['polling_interval']);
        }

        return $consumer;
    }

    /**
     * {@inheritdoc}
     */
    public function createProducer(): MessageProducerInterface
    {
        return new DbalMessageProducer($this->connection);
    }

    /**
     * {@inheritdoc}
     */
    public function close(): void
    {
    }

    public function getConnection(): ConnectionInterface
    {
        return $this->connection;
    }
}
