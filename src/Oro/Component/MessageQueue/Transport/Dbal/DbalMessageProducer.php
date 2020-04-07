<?php

namespace Oro\Component\MessageQueue\Transport\Dbal;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Type;
use Oro\Component\MessageQueue\Transport\ConnectionInterface;
use Oro\Component\MessageQueue\Transport\Exception\RuntimeException;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\MessageProducerInterface;
use Oro\Component\MessageQueue\Transport\QueueInterface;
use Oro\Component\MessageQueue\Util\JSON;

/**
 * Message producer for DBAL connection.
 * Stores all messages in the database.
 */
class DbalMessageProducer implements MessageProducerInterface
{
    /**
     * @var ConnectionInterface
     */
    private $connection;

    /**
     * @param ConnectionInterface $connection
     */
    public function __construct(ConnectionInterface $connection)
    {
        $this->connection = $connection;
    }

    /**
     * {@inheritdoc}
     */
    public function send(QueueInterface $queue, MessageInterface $message): void
    {
        $dbalMessage = [
            'body' => $message->getBody(),
            'headers' => JSON::encode($message->getHeaders()),
            'properties' => JSON::encode($message->getProperties()),
            'priority' => $message->getPriority(),
            'queue' => $queue->getQueueName(),
        ];

        $delay = $message->getDelay();
        if ($delay > 0) {
            $dbalMessage['delayed_until'] = time() + $delay;
        }

        try {
            /** @var Connection $dbalConnection */
            $dbalConnection = $this->connection->getDBALConnection();
            $dbalConnection->insert($this->connection->getTableName(), $dbalMessage, [
                'body' => Type::TEXT,
                'headers' => Type::TEXT,
                'properties' => Type::TEXT,
                'priority' => Type::SMALLINT,
                'queue' => Type::STRING,
                'delayed_until' => Type::INTEGER,
            ]);
        } catch (\Exception $e) {
            throw new RuntimeException(
                'The transport fails to send the message due to some internal error.',
                null,
                $e
            );
        }
    }
}
