<?php

namespace Oro\Component\MessageQueue\Transport\Dbal;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\Exception as DbalDriverException;
use Doctrine\DBAL\Driver\Statement;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Platforms\PostgreSqlPlatform;
use Doctrine\DBAL\Types\Types;
use Oro\Component\MessageQueue\Transport\Exception\InvalidMessageException;
use Oro\Component\MessageQueue\Transport\Exception\RuntimeException;
use Oro\Component\MessageQueue\Transport\MessageConsumerInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\QueueInterface;
use Oro\Component\MessageQueue\Util\JSON;

/**
 * Message consumer for DBAL connection.
 * Receives all messages from the database.
 */
class DbalMessageConsumer implements MessageConsumerInterface
{
    /**
     * @var DbalSessionInterface
     */
    private $session;

    /**
     * @var DbalConnection
     */
    private $connection;

    /**
     * @var Connection
     */
    private $dbal;

    /**
     * @var QueueInterface
     */
    private $queue;

    /**
     * @var string
     */
    private $consumerId;

    /**
     * @var int microseconds
     */
    private $pollingInterval = 1000000;

    /**
     * @var Statement
     */
    private $updateStatement;

    /**
     * @var Statement
     */
    private $selectStatement;

    /**
     * @var Statement
     */
    private $deleteStatement;

    public function __construct(DbalSessionInterface $session, QueueInterface $queue)
    {
        $this->session = $session;
        $this->queue = $queue;
        $this->connection = $session->getConnection();
        $this->dbal = $this->connection->getDBALConnection();
        $this->consumerId = uniqid('', true);
    }

    public function getConsumerId(): string
    {
        return $this->consumerId;
    }

    /**
     * Set polling interval in milliseconds
     */
    public function setPollingInterval(int $msec): void
    {
        $this->pollingInterval = $msec * 1000;
    }

    /**
     * Get polling interval in milliseconds
     */
    public function getPollingInterval(): int
    {
        return $this->pollingInterval / 1000;
    }

    /**
     * {@inheritdoc}
     */
    public function receive($timeout = 0): ?MessageInterface
    {
        $startAt = microtime(true);

        while (true) {
            $message = $this->receiveMessage();

            if ($message) {
                return $message;
            }

            if ($timeout && (microtime(true) - $startAt) >= $timeout) {
                return null;
            }

            usleep($this->pollingInterval);

            if ($timeout && (microtime(true) - $startAt) >= $timeout) {
                return null;
            }
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function acknowledge(MessageInterface $message): void
    {
        if (!$message instanceof DbalMessageInterface) {
            throw new InvalidMessageException(
                sprintf('The transport message must be instance of "%s".', DbalMessageInterface::class)
            );
        }

        $this->deleteMessageWithRetry($message);
    }

    /**
     * {@inheritdoc}
     */
    public function reject(MessageInterface $message, $requeue = false): void
    {
        if (!$message instanceof DbalMessageInterface) {
            throw new InvalidMessageException(
                sprintf('The transport message must be instance of "%s".', DbalMessageInterface::class)
            );
        }

        if ($requeue) {
            $this->requeueMessageWithRetry($message);
        } else {
            $this->deleteMessageWithRetry($message);
        }
    }

    /**
     * Receive message, set consumer for message assigned to current queue
     * Return data of received message.
     *
     * @throws \Doctrine\DBAL\Exception
     */
    private function receiveMessage(): ?MessageInterface
    {
        $now = time();
        $this->getUpdateStatement()->execute([
            'queue' => $this->queue->getQueueName(),
            'delayedUntil' => $now,
            'consumerId' => $this->consumerId
        ]);
        $affectedRows = $this->getUpdateStatement()->rowCount();

        if (1 === $affectedRows) {
            $selectStatement = $this->getSelectStatement();
            $selectStatement->execute(
                [
                    'consumerId' => $this->consumerId,
                    'queue' => $this->queue->getQueueName(),
                ]
            );
            $dbalMessage = $selectStatement->fetch(\PDO::FETCH_ASSOC);

            if (false === $dbalMessage) {
                throw new \LogicException(sprintf(
                    'Expected one record but got nothing. consumer_id: "%s"',
                    $this->consumerId
                ));
            }

            return $this->convertMessage($dbalMessage);
        }

        return null;
    }

    private function convertMessage(array $dbalMessage): MessageInterface
    {
        $message = $this->session->createMessage();

        $message->setId($dbalMessage['id']);
        $message->setBody(JSON::decode($dbalMessage['body'], false) ?? $dbalMessage['body']);
        $message->setPriority((int)$dbalMessage['priority']);
        $message->setRedelivered((bool)$dbalMessage['redelivered']);

        if ($dbalMessage['headers']) {
            $message->setHeaders(JSON::decode($dbalMessage['headers']));
        }

        if ($dbalMessage['properties']) {
            $message->setProperties(JSON::decode($dbalMessage['properties']));
        }

        return $message;
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    private function getSelectStatement(): Statement
    {
        if (!$this->selectStatement) {
            $this->selectStatement = $this->dbal->prepare(
                sprintf(
                    'SELECT * FROM %s WHERE consumer_id=:consumerId AND queue=:queue LIMIT 1',
                    $this->connection->getTableName()
                )
            );
        }

        return $this->selectStatement;
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    private function getUpdateStatement(): Statement
    {
        if (!$this->updateStatement) {
            $databasePlatform = $this->dbal->getDatabasePlatform();
            if (is_a($databasePlatform, MySqlPlatform::class)) {
                $this->updateStatement = $this->dbal->prepare(sprintf(
                    'UPDATE %s SET consumer_id=:consumerId'
                    . ' WHERE consumer_id IS NULL AND queue=:queue'
                    . ' AND (delayed_until IS NULL OR delayed_until<=:delayedUntil)'
                    . ' ORDER BY priority DESC, id ASC LIMIT 1',
                    $this->connection->getTableName()
                ));
            } elseif (is_a($databasePlatform, PostgreSqlPlatform::class)) {
                $this->updateStatement = $this->dbal->prepare(sprintf(
                    'UPDATE %1$s SET consumer_id=:consumerId'
                    . ' WHERE id = (SELECT id FROM %1$s WHERE consumer_id IS NULL AND queue=:queue'
                    . ' AND (delayed_until IS NULL OR delayed_until<=:delayedUntil)'
                    . ' ORDER BY priority DESC, id ASC LIMIT 1 FOR UPDATE)',
                    $this->connection->getTableName()
                ));
            } else {
                throw new \LogicException('Unsupported database driver');
            }
        }

        return $this->updateStatement;
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    private function getDeleteStatement(): Statement
    {
        if (!$this->deleteStatement) {
            $databasePlatform = $this->dbal->getDatabasePlatform();
            if (is_a($databasePlatform, MySqlPlatform::class)) {
                $this->deleteStatement = $this->dbal->prepare(
                    sprintf('DELETE FROM %s WHERE id=:messageId LIMIT 1', $this->connection->getTableName())
                );
            } elseif (is_a($databasePlatform, PostgreSqlPlatform::class)) {
                $this->deleteStatement = $this->dbal->prepare(
                    sprintf('DELETE FROM %s WHERE id=:messageId', $this->connection->getTableName())
                );
            } else {
                throw new \LogicException('Unsupported database driver');
            }
        }

        return $this->deleteStatement;
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    private function deleteMessage(DbalMessageInterface $message): int
    {
        $deleteStatement = $this->getDeleteStatement();
        $deleteStatement->execute(['messageId' => $message->getId()]);

        return $deleteStatement->rowCount();
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    private function requeueMessage(DbalMessageInterface $message): int
    {
        return $this->dbal->update(
            $this->connection->getTableName(),
            ['consumer_id' => null, 'redelivered' => true],
            ['id' => $message->getId()],
            [
                'redelivered' => Types::BOOLEAN,
                'id' => Types::INTEGER,
            ]
        );
    }

    /**
     * Executes a closure with DBAL query.
     * Makes 3 tries with 1 second interval if {@see DbalDriverException} occurs.
     *
     * @param \Closure $closure Closure that executes DBAL query and returns a number of affected rows.
     *
     * @return int Number of affected rows
     *
     * @throws \Doctrine\DBAL\Exception|DbalDriverException
     */
    private function executeWithRetry(\Closure $closure): int
    {
        $try = 0;
        do {
            try {
                return $closure();
            } catch (DbalDriverException $driverException) {
                $try ++;
                sleep(1);
            }
        } while ($try < 3);

        throw $driverException;
    }

    /**
     * Deletes a message from queue.
     * Makes 3 tries with 1 second interval if {@see DbalDriverException} occurs.
     *
     * @throws \Doctrine\DBAL\Exception|DbalDriverException|RuntimeException
     */
    private function deleteMessageWithRetry(DbalMessageInterface $message): void
    {
        $affectedRows = $this->executeWithRetry(fn () => $this->deleteMessage($message));

        if (1 !== $affectedRows) {
            throw new RuntimeException(sprintf(
                'Failed to delete a message with id "%s". Expected 1 affected row, got %d.',
                $message->getId(),
                $affectedRows
            ));
        }
    }

    /**
     * Requeues a message in queue.
     * Makes 3 tries with 1 second interval if {@see DbalDriverException} occurs.
     *
     * @throws \Doctrine\DBAL\Exception|DbalDriverException|RuntimeException
     */
    private function requeueMessageWithRetry(DbalMessageInterface $message): void
    {
        $affectedRows = $this->executeWithRetry(fn () => $this->requeueMessage($message));

        if (1 !== $affectedRows) {
            throw new RuntimeException(sprintf(
                'Failed to requeue a message with id "%s". Expected 1 affected row, got %d.',
                $message->getId(),
                $affectedRows
            ));
        }
    }
}
