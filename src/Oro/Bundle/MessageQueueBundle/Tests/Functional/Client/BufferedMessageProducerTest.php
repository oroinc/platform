<?php

namespace Oro\Bundle\MessageQueueBundle\Tests\Functional\Client;

use Doctrine\DBAL\Connection;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueAssertTrait;
use Oro\Bundle\MessageQueueBundle\Tests\Functional\Environment\TestBufferedMessageProducer;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Transport\Dbal\DbalConnection;

class BufferedMessageProducerTest extends WebTestCase
{
    use MessageQueueAssertTrait;

    /** @var MessageProducerInterface */
    protected $producer;

    /** @var TestBufferedMessageProducer */
    protected $bufferedProducer;

    /** @var Connection */
    protected $connection;

    protected function setUp()
    {
        $this->initClient();
        $container = self::getContainer();
        $this->connection = $container->get('doctrine.dbal.default_connection');
        $this->producer = $container->get('oro_message_queue.client.message_producer');
        $this->bufferedProducer = $container->get('oro_message_queue.client.buffered_message_producer');
        $this->bufferedProducer->enable();

        // make sute that there are no active transactions
        while ($this->connection->isTransactionActive()) {
            $this->connection->rollBack();
        }
    }

    protected function tearDown()
    {
        $this->bufferedProducer->disable();
        self::getMessageCollector()->clear();

        // make sure that there are not messages in the database
        // it can happen because the database transaction was closed in setUp() method
        $container = self::getContainer();
        $dbalConnectionServiceId = 'oro_message_queue.transport.dbal.connection';
        if ($container->has($dbalConnectionServiceId)) {
            $connection = $container->get($dbalConnectionServiceId);
            if ($connection instanceof DbalConnection) {
                $connection->getDBALConnection()->executeQuery('DELETE FROM ' . $connection->getTableName());
            }
        }
    }

    public function testBufferModeDisabled()
    {
        $topic = 'test_buffered_queue_producer';
        $message = 'foo';

        // buffered producer should send messages directly omitting buffering
        $this->producer->send($topic, $message);

        self::assertMessagesSent($topic, [$message]);
    }

    public function testFlushBufferOnCommit()
    {
        $topic = 'test_buffered_queue_producer';
        $messages = ['foo', 'bar'];

        $this->connection->beginTransaction();
        try {
            foreach ($messages as $message) {
                $this->producer->send($topic, $message);
            }
            self::assertMessagesEmpty($topic);
        } finally {
            $this->connection->commit();
        }

        self::assertMessagesSent($topic, $messages);
    }

    public function testClearBufferOnRollback()
    {
        $topic = 'test_buffered_queue_producer';
        $messages = ['foo', 'bar'];

        $this->connection->beginTransaction();
        try {
            foreach ($messages as $message) {
                $this->producer->send($topic, $message);
            }
            self::assertMessagesEmpty($topic);
        } finally {
            $this->connection->rollBack();
        }

        self::assertAttributeEmpty('buffer', $this->bufferedProducer);
        self::assertMessagesEmpty($topic);
    }

    public function testNestedTransactions()
    {
        $topic = 'test_buffered_queue_producer';
        $messages = ['foo', 'bar'];

        // begin root transaction and send a message
        $this->connection->beginTransaction();
        try {
            $this->producer->send($topic, $messages[0]);
            self::assertMessagesEmpty($topic);

            // begin nested transaction and send a message
            $this->connection->beginTransaction();
            try {
                $this->producer->send($topic, $messages[1]);
                self::assertMessagesEmpty($topic);
            } finally {
                // the commit of nested transaction should not cause to send buffered messages to the queue
                $this->connection->commit();
            }
            self::assertMessagesEmpty($topic);
        } finally {
            // the commit of root transaction should cause to send all buffered messages to the queue
            $this->connection->commit();
        }
        self::assertMessagesSent($topic, $messages);
    }
}
