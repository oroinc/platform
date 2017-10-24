<?php
namespace Oro\Component\MessageQueue\Transport\Dbal;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Type;
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
     * @var int microseconds
     */
    private $pollingInterval = 1000000;

    /**
     * @var DbalMessage[]
     */
    private $prefetchMessages = [];

    /**
     * @var int
     */
    private $prefetchSize = 1;

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
     * Prefetch count for each consumer.
     *
     * @param int $prefetchSize
     */
    public function setPrefetchSize($prefetchSize)
    {
        $this->prefetchSize = $prefetchSize;
    }

    /**
     * Get polling interval in milliseconds
     *
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
        $startAt = microtime(true);

        while (true) {
            $message = $this->receiveMessage();

            if ($message) {
                return $message;
            }

            if ($timeout && (microtime(true) - $startAt) >= $timeout) {
                return;
            }

            usleep($this->pollingInterval);

            if ($timeout && (microtime(true) - $startAt) >= $timeout) {
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

        $this->dbal->beginTransaction();

        $sql = sprintf(
            'SELECT id FROM %s WHERE id=:id FOR UPDATE',
            $this->connection->getTableName()
        );

        $row = $this->dbal->executeQuery(
            $sql,
            ['id' => $message->getId(), ],
            ['id' => Type::INTEGER, ]
        )->fetch();
        $affectedRows = null;
        if (count($row)) {
            try {
                $affectedRows = $this->dbal->delete($this->connection->getTableName(), ['id' => $message->getId()], [
                    'id' => Type::INTEGER,
                ]);
                $this->dbal->commit();
            } catch (\Exception $e) {
                sleep(1);
                try {
                    $affectedRows = $this->dbal->delete(
                        $this->connection->getTableName(),
                        ['id' => $message->getId()],
                        ['id' => Type::INTEGER, ]
                    );
                    $this->dbal->commit();
                } catch (\Exception $e) {
                    $this->dbal->rollBack();
                }
            }
        }
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

        $this->dbal->beginTransaction();

        $sql = sprintf(
            'SELECT id FROM %s WHERE id=:id FOR UPDATE',
            $this->connection->getTableName()
        );

        $row = $this->dbal->executeQuery(
            $sql,
            [
                'id' => $message->getId(),
            ],
            [
                'id' => Type::INTEGER,
            ]
        )->fetch();
        $affectedRows = null;
        if (count($row)) {
            try {
                $affectedRows = $this->dbal->delete($this->connection->getTableName(), ['id' => $message->getId()], [
                    'id' => Type::INTEGER,
                ]);
                $this->dbal->commit();
            } catch (\Exception $e) {
                sleep(1);
                try {
                    $affectedRows = $this->dbal->delete(
                        $this->connection->getTableName(),
                        ['id' => $message->getId()],
                        ['id' => Type::INTEGER, ]
                    );
                    $this->dbal->commit();
                } catch (\Exception $e) {
                    $this->dbal->rollBack();
                }
            }
        }
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
     * @return DbalMessage|null
     */
    protected function receiveMessage()
    {
        if ($this->prefetchMessages) {
            return array_shift($this->prefetchMessages);
        }

        /*
         * Why this query is so terrible.
         * We need to update only one record ordered by priority and id
         * but postgres does not support "order by" in update query and
         * we use sub query but mysql raise error when sub query contains
         * same table as update query and the solution is to use one
         * more sub query.
         */
        $this->dbal->beginTransaction();
        $row = null;
        try {
            $now = time();

            $sql = sprintf(
                'SELECT id FROM %s WHERE queue=:queue AND consumer_id IS NULL AND ' .
                '(delayed_until IS NULL OR delayed_until<=:delayedUntil) ' .
                'ORDER BY priority DESC, id ASC LIMIT %s FOR UPDATE',
                $this->connection->getTableName(),
                $this->prefetchSize
            );

            $rows = $this->dbal->executeQuery(
                $sql,
                [
                    'queue' => $this->queue->getQueueName(),
                    'delayedUntil' => $now,
                ],
                [
                    'queue' => Type::STRING,
                    'delayedUntil' => Type::INTEGER,
                ]
            )->fetchAll();

            if ($rows) {
                $messageIds = array_column($rows, 'id');;

                $sql = sprintf(
                    'UPDATE %s SET consumer_id=:consumerId  WHERE id IN (:messageIds)',
                    $this->connection->getTableName()
                );

                $this->dbal->executeUpdate(
                    $sql,
                    [
                        'messageIds' => $messageIds,
                        'consumerId' => $this->consumerId
                    ],
                    [
                        'messageIds' => Connection::PARAM_STR_ARRAY,
                        'consumerId' => Type::STRING
                    ]
                );

                $sql = sprintf(
                    'SELECT * FROM %s WHERE consumer_id=:consumerId AND queue=:queue',
                    $this->connection->getTableName()
                );

                $dbalMessages = $this->dbal->executeQuery(
                    $sql,
                    [
                        'consumerId' => $this->consumerId,
                        'queue' => $this->queue->getQueueName(),
                    ],
                    [
                        'consumerId' => Type::STRING,
                        'queue' => Type::STRING,
                    ]
                )->fetchAll();

                if (false == $dbalMessages) {
                    throw new \LogicException(sprintf(
                        'Expected one record but got nothing. consumer_id: "%s"',
                        $this->consumerId
                    ));
                }
                $this->dbal->commit();

                foreach ($dbalMessages as $dbalMessage) {
                    $this->prefetchMessages[] = $this->convertMessage($dbalMessage);
                }

                return array_shift($this->prefetchMessages);
            }

            $this->dbal->commit();
        } catch (\LogicException $e) {
            $this->dbal->rollBack();
            throw ($e);
        } catch (\Exception $e) {
            $this->dbal->rollBack();
        }
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

    /**
     * @return string
     */
    public function getId()
    {
        return $this->consumerId;
    }
}
