<?php

namespace Oro\Bundle\MessageQueueBundle\Tests\Functional\Client;

use Doctrine\DBAL\Connection;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueAssertTrait;
use Oro\Bundle\MessageQueueBundle\Tests\Functional\Environment\TestBufferedMessageProducer;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Test\Async\Topic\BasicMessageTestTopic;
use Oro\Component\MessageQueue\Transport\Dbal\DbalConnection;

class BufferedMessageProducerTest extends WebTestCase
{
    use MessageQueueAssertTrait;

    /** @var MessageProducerInterface */
    private $producer;

    /** @var TestBufferedMessageProducer */
    private $bufferedProducer;

    /** @var Connection */
    private $connection;

    protected function setUp(): void
    {
        $this->initClient();
        $container = self::getContainer();
        $this->connection = $container->get('doctrine.dbal.default_connection');
        $this->producer = $container->get('oro_message_queue.client.message_producer');
        $this->bufferedProducer = $container->get('oro_message_queue.client.buffered_message_producer');
    }

    protected function tearDown(): void
    {
        self::getMessageCollector()->clear();

        // make sure that there are no messages in the database
        // it can happen because the database transaction was closed in setUp() method
        $container = self::getContainer();
        $connection = $container->get('oro_message_queue.transport.connection');
        if ($connection instanceof DbalConnection) {
            $connection->getDBALConnection()->executeQuery('DELETE FROM ' . $connection->getTableName());
        }
    }

    public function testBufferModeDisabled(): void
    {
        $topic = BasicMessageTestTopic::getName();
        $message = ['message' => 'foo'];

        // buffered producer should send messages directly omitting buffering
        $this->producer->send($topic, $message);

        self::assertMessagesSent($topic, [$message]);
    }

    public function testForceDisableBufferModeWhenEnableBufferingNestingLevelIsOne(): void
    {
        $topic = BasicMessageTestTopic::getName();
        $message = ['message' => 'foo'];

        $this->bufferedProducer->enableBuffering();
        $this->bufferedProducer->disable();
        try {
            // buffered producer should send messages directly omitting buffering
            $this->producer->send($topic, $message);
            self::assertMessagesSent($topic, [$message]);
            self::assertTrue($this->bufferedProducer->isBufferingEnabled());
        } finally {
            $this->bufferedProducer->enable();
            $this->bufferedProducer->disableBuffering();
        }
        self::assertFalse($this->bufferedProducer->isBufferingEnabled());
    }

    public function testForceDisableBufferModeWhenEnableBufferingNestingLevelIsMoreThanOne(): void
    {
        $topic = BasicMessageTestTopic::getName();
        $message = ['message' => 'foo'];

        $this->bufferedProducer->enableBuffering();
        $this->bufferedProducer->enableBuffering();
        $this->bufferedProducer->enableBuffering();
        $this->bufferedProducer->disable();
        try {
            // buffered producer should send messages directly omitting buffering
            $this->producer->send($topic, $message);
            self::assertMessagesSent($topic, [$message]);
            self::assertTrue($this->bufferedProducer->isBufferingEnabled());
        } finally {
            $this->bufferedProducer->enable();
            $this->bufferedProducer->disableBuffering();
            $this->bufferedProducer->disableBuffering();
            $this->bufferedProducer->disableBuffering();
        }
        self::assertFalse($this->bufferedProducer->isBufferingEnabled());
    }

    public function testFlushBufferOnCommit(): void
    {
        $topic = BasicMessageTestTopic::getName();
        $messages = [
            ['message' => 'foo'],
            ['message' => 'bar'],
        ];

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

    public function testNestedTransactions(): void
    {
        $topic = BasicMessageTestTopic::getName();
        $messages = [
            ['message' => 'foo'],
            ['message' => 'bar'],
        ];

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

    public function testClearBufferOnRollback(): void
    {
        $topic = BasicMessageTestTopic::getName();
        $messages = [
            ['message' => 'foo'],
            ['message' => 'bar'],
        ];

        $this->connection->beginTransaction();
        try {
            foreach ($messages as $message) {
                $this->producer->send($topic, $message);
            }
            self::assertMessagesEmpty($topic);
        } finally {
            $this->connection->rollBack();
        }

        self::assertFalse($this->bufferedProducer->hasBufferedMessages());
        self::assertMessagesEmpty($topic);
    }
}
