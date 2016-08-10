<?php
namespace Oro\Component\MessageQueue\Transport\Dbal;

use Doctrine\DBAL\Connection;
use Oro\Component\MessageQueue\Transport\Exception\InvalidMessageException;
use Oro\Component\MessageQueue\Transport\MessageConsumerInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Util\JSON;

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
     * @var int
     */
    private $pollingInterval = 1000000;

    /**
     * @param DbalSession     $session
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
     * Set polling interval in milliseconds
     *
     * @param int $msec
     */
    public function setPollingInterval($msec)
    {
        $this->pollingInterval = $msec * 1000;
    }

    /**
     * @return int
     */
    public function getPollingInterval()
    {
        return (int) $this->pollingInterval / 1000;
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
        $startAt = time();

        while (true) {
            $message = $this->receiveMessage();

            if ($message) {
                return $message;
            }

            if ($timeout && (time() - $startAt) >= $timeout) {
                return;
            }

            usleep($this->pollingInterval);

            if ($timeout && (time() - $startAt) >= $timeout) {
                return;
            }
        }
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

        $affectedRows = $this->dbal->delete($this->connection->getTableName(), ['id' => $message->getId()]);

        if (1 !== $affectedRows) {
            throw new \LogicException(sprintf(
                'Expected record was removed but it is not. id: "%s"',
                $message->getId()
            ));
        }
    }

    /**
     * {@inheritdoc}
     *
     * @param DbalMessage $message
     */
    public function reject(MessageInterface $message, $requeue = false)
    {
        InvalidMessageException::assertMessageInstanceOf($message, DbalMessage::class);

        $affectedRows = $this->dbal->delete($this->connection->getTableName(), ['id' => $message->getId()]);

        if (1 !== $affectedRows) {
            throw new \LogicException(sprintf(
                'Expected record was removed but it is not. id: "%s"',
                $message->getId()
            ));
        }

        if ($requeue) {
            $dbalMessage = [
                'body' => $message->getBody(),
                'headers' => JSON::encode($message->getHeaders()),
                'properties' => JSON::encode($message->getProperties()),
                'priority' => $message->getPriority(),
                'queue' => $this->queue->getQueueName(),
                'redelivered' => true,
            ];

            $affectedRows = $this->dbal->insert($this->connection->getTableName(), $dbalMessage);

            if (1 !== $affectedRows) {
                throw new \LogicException(sprintf(
                    'Expected record was inserted but it is not. message: "%s"',
                    JSON::encode($dbalMessage)
                ));
            }
        }
    }

    /**
     * @return DbalMessage|null
     */
    protected function receiveMessage()
    {
        /*
         * Why this query is so terrible.
         * We need to update only one record ordered by priority and id
         * but postgres does not support "order by" in update query and
         * we use sub query but mysql raise error when sub query contains
         * same table as update query and the solution is to use one
         * more sub query.
         */
        $sql = sprintf(
            'UPDATE %s SET consumer_id=?, delivered_at=? '.
            'WHERE id = (SELECT id FROM ('.
            'SELECT id FROM %s WHERE queue=? AND consumer_id IS NULL AND (delayed_until IS NULL OR delayed_until<=?) '.
            'ORDER BY priority DESC, id ASC LIMIT 1'.
            ') AS x )',
            $this->connection->getTableName(),
            $this->connection->getTableName()
        );

        $now = time();

        $affectedRows = $this->dbal->executeUpdate($sql, [$this->consumerId, $now, $this->queue->getQueueName(), $now]);

        if (0 === $affectedRows) {
            return;
        }

        if (1 === $affectedRows) {
            $sql = sprintf(
                'SELECT * FROM %s WHERE consumer_id=? AND queue=? LIMIT 1',
                $this->connection->getTableName()
            );

            $dbalMessage = $this->dbal->executeQuery($sql, [$this->consumerId, $this->queue->getQueueName()])->fetch();

            if (false == $dbalMessage) {
                throw new \LogicException(sprintf(
                    'Expected one record but got nothing. consumer_id: "%s"',
                    $this->consumerId
                ));
            }

            return $this->convertMessage($dbalMessage);
        }

        if ($affectedRows > 1) {
            throw new \LogicException(sprintf(
                'Expected only one record but got more. consumer_id: "%s", count: "%s"',
                $this->consumerId,
                $affectedRows
            ));
        }

        // should never reach this line
        throw new \LogicException('Unpredictable error happened');
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
        $message->setPriority((int) $dbalMessage['priority']);
        $message->setRedelivered((bool) $dbalMessage['redelivered']);

        if ($dbalMessage['headers']) {
            $message->setHeaders(JSON::decode($dbalMessage['headers']));
        }

        if ($dbalMessage['properties']) {
            $message->setProperties(JSON::decode($dbalMessage['properties']));
        }

        return $message;
    }
}
