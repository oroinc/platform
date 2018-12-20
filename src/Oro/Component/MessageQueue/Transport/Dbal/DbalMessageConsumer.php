<?php

namespace Oro\Component\MessageQueue\Transport\Dbal;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\DriverException;
use Doctrine\DBAL\Driver\Statement;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Platforms\PostgreSqlPlatform;
use Doctrine\DBAL\Types\Type;
use Oro\Component\MessageQueue\Transport\Exception\InvalidMessageException;
use Oro\Component\MessageQueue\Transport\MessageConsumerInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Util\JSON;

/**
 * Consume messages from DBAL
 */
class DbalMessageConsumer implements MessageConsumerInterface
{
    /**
     * @var DbalSession
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
     * @var DbalDestination
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

    /**
     * @param DbalSession $session
     * @param DbalDestination $queue
     */
    public function __construct(DbalSession $session, DbalDestination $queue)
    {
        $this->session = $session;
        $this->queue = $queue;
        $this->connection = $session->getConnection();
        $this->dbal = $this->connection->getDBALConnection();
        $this->consumerId = uniqid('', true);
    }

    /**
     * @return string
     */
    public function getConsumerId()
    {
        return $this->consumerId;
    }

    /**
     * Set polling interval in milliseconds
     *
     * @param int $msec
     */
    public function setPollingInterval($msec)
    {
        $this->pollingInterval = $msec * 1000;
    }

    /**
     * Get polling interval in milliseconds
     *
     * @return int
     */
    public function getPollingInterval()
    {
        return (int)$this->pollingInterval / 1000;
    }

    /**
     * {@inheritdoc}
     *
     * @return DbalDestination
     */
    public function getQueue()
    {
        return $this->queue;
    }

    /**
     * {@inheritdoc}
     *
     * @return DbalMessage|null
     */
    public function receive($timeout = 0)
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
     *
     * @return DbalMessage|null
     */
    public function receiveNoWait()
    {
        return $this->receiveMessage();
    }

    /**
     * {@inheritdoc}
     *
     * @param DbalMessage $message
     */
    public function acknowledge(MessageInterface $message)
    {
        InvalidMessageException::assertMessageInstanceOf($message, DbalMessage::class);
        $this->deleteMessageWithRetry($message);
    }

    /**
     * {@inheritdoc}
     *
     * @param DbalMessage $message
     */
    public function reject(MessageInterface $message, $requeue = false)
    {
        InvalidMessageException::assertMessageInstanceOf($message, DbalMessage::class);
        $this->deleteMessageWithRetry($message);

        if ($requeue) {
            $dbalMessage = [
                'body' => $message->getBody(),
                'headers' => JSON::encode($message->getHeaders()),
                'properties' => JSON::encode($message->getProperties()),
                'priority' => $message->getPriority(),
                'queue' => $this->queue->getQueueName(),
                'redelivered' => true,
            ];

            $affectedRows = $this->dbal->insert($this->connection->getTableName(), $dbalMessage, [
                'body' => Type::TEXT,
                'headers' => Type::TEXT,
                'properties' => Type::TEXT,
                'priority' => Type::SMALLINT,
                'queue' => Type::STRING,
                'redelivered' => Type::BOOLEAN,
            ]);

            if (1 !== $affectedRows) {
                throw new \LogicException(sprintf(
                    'Expected record was inserted but it is not. message: "%s"',
                    JSON::encode($dbalMessage)
                ));
            }
        }
    }

    /**
     * Receive message, set consumer for message assigned to current queue
     * Return data of received message.
     *
     * @return DbalMessage|null
     * @throws \Doctrine\DBAL\DBALException
     */
    protected function receiveMessage()
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

    /**
     * @param array $dbalMessage
     *
     * @return DbalMessage
     */
    protected function convertMessage(array $dbalMessage)
    {
        $message = $this->session->createMessage();

        $message->setId($dbalMessage['id']);
        $message->setBody($dbalMessage['body']);
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
     * @return Statement
     * @throws \Doctrine\DBAL\DBALException
     */
    private function getSelectStatement()
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
     * @return Statement
     * @throws \Doctrine\DBAL\DBALException
     */
    private function getUpdateStatement()
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
     * @return Statement
     * @throws \Doctrine\DBAL\DBALException
     */
    private function getDeleteStatement()
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
     * @param DbalMessage|MessageInterface $message
     * @return int
     * @throws \Doctrine\DBAL\DBALException
     */
    private function deleteMessage(MessageInterface $message)
    {
        $deleteStatement = $this->getDeleteStatement();
        $deleteStatement->execute(['messageId' => $message->getId()]);

        return $deleteStatement->rowCount();
    }

    /**
     * Try to delete message from queue
     * retry once with delay of 1 second if DB query failed
     *
     * @param DbalMessage|MessageInterface $message
     * @throws \Doctrine\DBAL\DBALException|\LogicException
     */
    private function deleteMessageWithRetry(MessageInterface $message)
    {
        try {
            $affectedRows = $this->deleteMessage($message);
        } catch (DriverException $e) {
            sleep(1);
            $affectedRows = $this->deleteMessage($message);
        }

        if (1 !== $affectedRows) {
            throw new \LogicException(sprintf(
                'Expected record was removed but it is not. id: "%s"',
                $message->getId()
            ));
        }
    }
}
