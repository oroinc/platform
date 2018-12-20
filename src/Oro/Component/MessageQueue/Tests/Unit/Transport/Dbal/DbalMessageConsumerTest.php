<?php

namespace Oro\Component\MessageQueue\Tests\Unit\Transport\Dbal;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\PDOException;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Statement;
use Oro\Component\MessageQueue\Transport\Dbal\DbalConnection;
use Oro\Component\MessageQueue\Transport\Dbal\DbalDestination;
use Oro\Component\MessageQueue\Transport\Dbal\DbalMessage;
use Oro\Component\MessageQueue\Transport\Dbal\DbalMessageConsumer;
use Oro\Component\MessageQueue\Transport\Dbal\DbalSession;
use Oro\Component\MessageQueue\Transport\Exception\InvalidMessageException;
use Oro\Component\MessageQueue\Transport\Null\NullMessage;
use Oro\Component\MessageQueue\Util\JSON;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class DbalMessageConsumerTest extends \PHPUnit\Framework\TestCase
{
    /** @var DbalSession|\PHPUnit\Framework\MockObject\MockObject */
    private $session;

    /** @var DbalDestination|\PHPUnit\Framework\MockObject\MockObject */
    private $queue;

    /** @var DbalConnection|\PHPUnit\Framework\MockObject\MockObject */
    private $connection;

    /** @var Connection|\PHPUnit\Framework\MockObject\MockObject */
    private $dbal;

    /** @var DbalMessageConsumer */
    private $consumer;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->dbal = $this->createMock(Connection::class);
        $this->connection = $this->createMock(DbalConnection::class);
        $this->connection->expects($this->any())
            ->method('getDBALConnection')
            ->willReturn($this->dbal);
        $this->session = $this->createMock(DbalSession::class);
        $this->session->expects($this->any())
            ->method('getConnection')
            ->willReturn($this->connection);
        $this->queue = $this->createMock(DbalDestination::class);

        $this->consumer = new DbalMessageConsumer($this->session, $this->queue);
    }

    public function testGetConsumerId()
    {
        $consumer = new DbalMessageConsumer($this->session, $this->queue);
        $this->assertNotEquals($consumer->getConsumerId(), $this->consumer->getConsumerId());
    }

    public function testPollingInterval()
    {
        $this->assertEquals(1000, $this->consumer->getPollingInterval());

        $this->consumer->setPollingInterval(5);
        $this->assertEquals(5, $this->consumer->getPollingInterval());

        $this->consumer->setPollingInterval(1.55556);
        $this->assertEquals(1.555, $this->consumer->getPollingInterval());
    }

    public function testGetQueue()
    {
        $this->queue
            ->expects($this->once())
            ->method('getQueueName')
            ->willReturn('test');

        $this->assertEquals('test', $this->consumer->getQueue()->getQueueName());
    }

    public function testReceiveWithMessage()
    {
        $this->queue->expects($this->exactly(2))
            ->method('getQueueName')
            ->willReturn('test_queue');

        /** @var Statement|\PHPUnit\Framework\MockObject\MockObject $statement */
        $updateStatement = $this->createMock(Statement::class);
        $updateStatement->expects($this->once())
            ->method('execute')
            ->with($this->logicalAnd(
                $this->contains('test_queue'),
                $this->contains($this->consumer->getConsumerId())
            ));
        $updateStatement->expects($this->once())
            ->method('rowCount')
            ->willReturn(1);

        /** @var Statement|\PHPUnit\Framework\MockObject\MockObject $statement */
        $selectStatement = $this->createMock(Statement::class);
        $selectStatement->expects($this->once())
            ->method('execute')
            ->with([
                'consumerId' => $this->consumer->getConsumerId(),
                'queue' => 'test_queue',
            ]);
        $selectStatement->expects($this->once())
            ->method('fetch')
            ->with(2)
            ->willReturn([
                'id' => 'message.id',
                'body' => 'message.body',
                'priority' => 1,
                'redelivered' => false,
                'headers' => '{"header.key":"header.value"}',
                'properties' => '{"property.key":"property.value"}',
            ]);

        $this->dbal->expects($this->once())
            ->method('getDatabasePlatform')
            ->willReturn(new MySqlPlatform());

        $this->dbal->expects($this->exactly(2))
            ->method('prepare')
            ->willReturn($updateStatement, $selectStatement);

        $this->connection->expects($this->exactly(2))
            ->method('getTableName')
            ->willReturn('oro_message_queue');

        $this->session->expects($this->once())
            ->method('createMessage')
            ->willReturn(new DbalMessage());

        $expectedMessage = new DbalMessage();
        $expectedMessage->setId('message.id');
        $expectedMessage->setBody('message.body');
        $expectedMessage->setPriority(1);
        $expectedMessage->setHeaders(['header.key' => 'header.value']);
        $expectedMessage->setProperties(['property.key' => 'property.value']);

        $this->assertEquals($expectedMessage, $this->consumer->receive(1));
    }

    public function testReceiveWithMessageLogicException()
    {
        $this->queue->expects($this->exactly(2))
            ->method('getQueueName')
            ->willReturn('test_queue');

        /** @var Statement|\PHPUnit\Framework\MockObject\MockObject $statement */
        $updateStatement = $this->createMock(Statement::class);
        $updateStatement->expects($this->once())
            ->method('execute')
            ->with($this->logicalAnd(
                $this->contains('test_queue'),
                $this->contains($this->consumer->getConsumerId())
            ));
        $updateStatement->expects($this->once())
            ->method('rowCount')
            ->willReturn(1);

        /** @var Statement|\PHPUnit\Framework\MockObject\MockObject $statement */
        $selectStatement = $this->createMock(Statement::class);
        $selectStatement->expects($this->once())
            ->method('execute')
            ->with([
                'consumerId' => $this->consumer->getConsumerId(),
                'queue' => 'test_queue',
            ]);
        $selectStatement->expects($this->once())
            ->method('fetch')
            ->with(2)
            ->willReturn(false);

        $this->dbal->expects($this->once())
            ->method('getDatabasePlatform')
            ->willReturn(new MySqlPlatform());

        $this->dbal->expects($this->exactly(2))
            ->method('prepare')
            ->willReturn($updateStatement, $selectStatement);

        $this->connection->expects($this->exactly(2))
            ->method('getTableName')
            ->willReturn('oro_message_queue');

        $this->session->expects($this->never())
            ->method('createMessage');

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(
            sprintf('Expected one record but got nothing. consumer_id: "%s"', $this->consumer->getConsumerId())
        );

        $this->consumer->receive(1);
    }

    public function testReceiveWithoutMessage()
    {
        $this->queue->expects($this->once())
            ->method('getQueueName')
            ->willReturn('test_queue');

        /** @var Statement|\PHPUnit\Framework\MockObject\MockObject $statement */
        $updateStatement = $this->createMock(Statement::class);
        $updateStatement->expects($this->once())
            ->method('execute')
            ->with($this->logicalAnd(
                $this->contains('test_queue'),
                $this->contains($this->consumer->getConsumerId())
            ));
        $updateStatement->expects($this->once())
            ->method('rowCount')
            ->willReturn(0);

        $this->dbal->expects($this->once())
            ->method('getDatabasePlatform')
            ->willReturn(new MySqlPlatform());

        $this->dbal->expects($this->once())
            ->method('prepare')
            ->willReturn($updateStatement);

        $this->connection->expects($this->once())
            ->method('getTableName')
            ->willReturn('oro_message_queue');

        $this->session->expects($this->never())
            ->method('createMessage');

        $this->assertNull($this->consumer->receive(1));
    }

    public function testReceiveThrowLogicException()
    {
        $this->dbal->expects($this->once())
            ->method('getDatabasePlatform')
            ->willReturn(new \stdClass());

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Unsupported database driver');

        $this->consumer->receive(1);
    }

    public function testReceiveNoWaitWithMessage()
    {
        $this->queue->expects($this->exactly(2))
            ->method('getQueueName')
            ->willReturn('test_queue');

        /** @var Statement|\PHPUnit\Framework\MockObject\MockObject $statement */
        $updateStatement = $this->createMock(Statement::class);
        $updateStatement->expects($this->once())
            ->method('execute')
            ->with($this->logicalAnd(
                $this->contains('test_queue'),
                $this->contains($this->consumer->getConsumerId())
            ));
        $updateStatement->expects($this->once())
            ->method('rowCount')
            ->willReturn(1);

        /** @var Statement|\PHPUnit\Framework\MockObject\MockObject $statement */
        $selectStatement = $this->createMock(Statement::class);
        $selectStatement->expects($this->once())
            ->method('execute')
            ->with([
                'consumerId' => $this->consumer->getConsumerId(),
                'queue' => 'test_queue',
            ]);
        $selectStatement->expects($this->once())
            ->method('fetch')
            ->with(2)
            ->willReturn([
                'id' => 'message.id',
                'body' => 'message.body',
                'priority' => 1,
                'redelivered' => false,
                'headers' => '{"header.key":"header.value"}',
                'properties' => '{"property.key":"property.value"}',
            ]);

        $this->dbal->expects($this->once())
            ->method('getDatabasePlatform')
            ->willReturn(new MySqlPlatform());

        $this->dbal->expects($this->exactly(2))
            ->method('prepare')
            ->willReturn($updateStatement, $selectStatement);

        $this->connection->expects($this->exactly(2))
            ->method('getTableName')
            ->willReturn('oro_message_queue');

        $this->session->expects($this->once())
            ->method('createMessage')
            ->willReturn(new DbalMessage());

        $expectedMessage = new DbalMessage();
        $expectedMessage->setId('message.id');
        $expectedMessage->setBody('message.body');
        $expectedMessage->setPriority(1);
        $expectedMessage->setHeaders(['header.key' => 'header.value']);
        $expectedMessage->setProperties(['property.key' => 'property.value']);

        $this->assertEquals($expectedMessage, $this->consumer->receiveNoWait());
    }

    public function testReceiveNoWaitWithMessageLogicException()
    {
        $this->queue->expects($this->exactly(2))
            ->method('getQueueName')
            ->willReturn('test_queue');

        /** @var Statement|\PHPUnit\Framework\MockObject\MockObject $statement */
        $updateStatement = $this->createMock(Statement::class);
        $updateStatement->expects($this->once())
            ->method('execute')
            ->with($this->logicalAnd(
                $this->contains('test_queue'),
                $this->contains($this->consumer->getConsumerId())
            ));
        $updateStatement->expects($this->once())
            ->method('rowCount')
            ->willReturn(1);

        /** @var Statement|\PHPUnit\Framework\MockObject\MockObject $statement */
        $selectStatement = $this->createMock(Statement::class);
        $selectStatement->expects($this->once())
            ->method('execute')
            ->with([
                'consumerId' => $this->consumer->getConsumerId(),
                'queue' => 'test_queue',
            ]);
        $selectStatement->expects($this->once())
            ->method('fetch')
            ->with(2)
            ->willReturn(false);

        $this->dbal->expects($this->once())
            ->method('getDatabasePlatform')
            ->willReturn(new MySqlPlatform());

        $this->dbal->expects($this->exactly(2))
            ->method('prepare')
            ->willReturn($updateStatement, $selectStatement);

        $this->connection->expects($this->exactly(2))
            ->method('getTableName')
            ->willReturn('oro_message_queue');

        $this->session->expects($this->never())
            ->method('createMessage');

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(
            sprintf('Expected one record but got nothing. consumer_id: "%s"', $this->consumer->getConsumerId())
        );

        $this->consumer->receiveNoWait();
    }

    public function testReceiveNoWaitWithoutMessage()
    {
        $this->queue->expects($this->once())
            ->method('getQueueName')
            ->willReturn('test_queue');

        /** @var Statement|\PHPUnit\Framework\MockObject\MockObject $statement */
        $updateStatement = $this->createMock(Statement::class);
        $updateStatement->expects($this->once())
            ->method('execute')
            ->with($this->logicalAnd(
                $this->contains('test_queue'),
                $this->contains($this->consumer->getConsumerId())
            ));
        $updateStatement->expects($this->once())
            ->method('rowCount')
            ->willReturn(0);

        $this->dbal->expects($this->once())
            ->method('getDatabasePlatform')
            ->willReturn(new MySqlPlatform());

        $this->dbal->expects($this->once())
            ->method('prepare')
            ->willReturn($updateStatement);

        $this->connection->expects($this->once())
            ->method('getTableName')
            ->willReturn('oro_message_queue');

        $this->session->expects($this->never())
            ->method('createMessage');

        $this->assertNull($this->consumer->receiveNoWait());
    }

    public function testReceiveNoWaitThrowLogicException()
    {
        $this->dbal->expects($this->once())
            ->method('getDatabasePlatform')
            ->willReturn(new \stdClass());

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Unsupported database driver');

        $this->consumer->receiveNoWait();
    }

    public function testAcknowledge()
    {
        /** @var Statement|\PHPUnit\Framework\MockObject\MockObject $statement */
        $deleteStatement = $this->createMock(Statement::class);
        $deleteStatement->expects($this->once())
            ->method('execute')
            ->with([
                'messageId' => 'message.id'
            ]);

        $deleteStatement->expects($this->once())
            ->method('rowCount')
            ->willReturn(1);

        $this->dbal->expects($this->once())
            ->method('getDatabasePlatform')
            ->willReturn(new MySqlPlatform());

        $this->dbal->expects($this->once())
            ->method('prepare')
            ->willReturn($deleteStatement);

        $message = new DbalMessage();
        $message->setId('message.id');

        $this->consumer->acknowledge($message);
    }

    public function testAcknowledgeWithRetry()
    {
        /** @var Statement|\PHPUnit\Framework\MockObject\MockObject $statement */
        $deleteStatement = $this->createMock(Statement::class);
        $deleteStatement->expects($this->exactly(2))
            ->method('execute')
            ->with([
                'messageId' => 'message.id'
            ]);

        $deleteStatement->expects($this->exactly(2))
            ->method('rowCount')
            ->willReturn($this->returnCallback(function () {
                throw new PDOException(new \PDOException());
            }), 1);

        $this->dbal->expects($this->once())
            ->method('getDatabasePlatform')
            ->willReturn(new MySqlPlatform());

        $this->dbal->expects($this->once())
            ->method('prepare')
            ->willReturn($deleteStatement);

        $message = new DbalMessage();
        $message->setId('message.id');

        $this->consumer->acknowledge($message);
    }

    public function testAcknowledgeLogicException()
    {
        /** @var Statement|\PHPUnit\Framework\MockObject\MockObject $statement */
        $deleteStatement = $this->createMock(Statement::class);
        $deleteStatement->expects($this->once())
            ->method('execute')
            ->with([
                'messageId' => 'message.id'
            ]);

        $deleteStatement->expects($this->once())
            ->method('rowCount')
            ->willReturn(0);

        $this->dbal->expects($this->once())
            ->method('getDatabasePlatform')
            ->willReturn(new MySqlPlatform());

        $this->dbal->expects($this->once())
            ->method('prepare')
            ->willReturn($deleteStatement);

        $message = new DbalMessage();
        $message->setId('message.id');

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Expected record was removed but it is not. id: "message.id"');

        $this->consumer->acknowledge($message);
    }

    public function testAcknowledgeInvalidMessageException()
    {
        $this->expectException(InvalidMessageException::class);
        $this->expectExceptionMessage(
            sprintf('The message must be an instance of %s but it is %s.', DbalMessage::class, NullMessage::class)
        );

        $this->consumer->acknowledge(new NullMessage());
    }

    public function testReject()
    {
        /** @var Statement|\PHPUnit\Framework\MockObject\MockObject $statement */
        $deleteStatement = $this->createMock(Statement::class);
        $deleteStatement->expects($this->once())
            ->method('execute')
            ->with([
                'messageId' => 'message.id'
            ]);

        $deleteStatement->expects($this->once())
            ->method('rowCount')
            ->willReturn(1);

        $this->dbal->expects($this->once())
            ->method('getDatabasePlatform')
            ->willReturn(new MySqlPlatform());

        $this->dbal->expects($this->once())
            ->method('prepare')
            ->willReturn($deleteStatement);

        $message = new DbalMessage();
        $message->setId('message.id');

        $this->consumer->reject($message);
    }

    public function testRejectWithRetry()
    {
        /** @var Statement|\PHPUnit\Framework\MockObject\MockObject $statement */
        $deleteStatement = $this->createMock(Statement::class);
        $deleteStatement->expects($this->exactly(2))
            ->method('execute')
            ->with([
                'messageId' => 'message.id'
            ]);

        $deleteStatement->expects($this->exactly(2))
            ->method('rowCount')
            ->willReturn($this->returnCallback(function () {
                throw new PDOException(new \PDOException());
            }), 1);

        $this->dbal->expects($this->once())
            ->method('getDatabasePlatform')
            ->willReturn(new MySqlPlatform());

        $this->dbal->expects($this->once())
            ->method('prepare')
            ->willReturn($deleteStatement);

        $message = new DbalMessage();
        $message->setId('message.id');

        $this->consumer->reject($message);
    }

    public function testRejectRequeue()
    {
        $this->queue->expects($this->once())
            ->method('getQueueName')
            ->willReturn('test_queue');

        /** @var Statement|\PHPUnit\Framework\MockObject\MockObject $statement */
        $deleteStatement = $this->createMock(Statement::class);
        $deleteStatement->expects($this->once())
            ->method('execute')
            ->with([
                'messageId' => 'message.id'
            ]);

        $deleteStatement->expects($this->once())
            ->method('rowCount')
            ->willReturn(1);

        $this->dbal->expects($this->once())
            ->method('getDatabasePlatform')
            ->willReturn(new MySqlPlatform());

        $this->dbal->expects($this->once())
            ->method('prepare')
            ->willReturn($deleteStatement);

        $this->dbal->expects($this->once())
            ->method('insert')
            ->with('oro_message_queue', [
                'body' => 'message.body',
                'headers' => '{"header.key":"header.value"}',
                'properties' => '{"property.key":"property.value"}',
                'priority' => 1,
                'queue' => 'test_queue',
                'redelivered' => true,
            ], [
                'body' => 'text',
                'headers' => 'text',
                'properties' => 'text',
                'priority' => 'smallint',
                'queue' => 'string',
                'redelivered' => 'boolean',
            ])
            ->willReturn(1);

        $this->connection->expects($this->exactly(2))
            ->method('getTableName')
            ->willReturn('oro_message_queue');

        $message = new DbalMessage();
        $message->setId('message.id');
        $message->setBody('message.body');
        $message->setHeaders(['header.key' => 'header.value']);
        $message->setProperties(['property.key' => 'property.value']);
        $message->setPriority(1);
        $message->setRedelivered(true);

        $this->consumer->reject($message, true);
    }

    public function testRejectLogicException()
    {
        /** @var Statement|\PHPUnit\Framework\MockObject\MockObject $statement */
        $deleteStatement = $this->createMock(Statement::class);
        $deleteStatement->expects($this->once())
            ->method('execute')
            ->with([
                'messageId' => 'message.id'
            ]);

        $deleteStatement->expects($this->once())
            ->method('rowCount')
            ->willReturn(0);

        $this->dbal->expects($this->once())
            ->method('getDatabasePlatform')
            ->willReturn(new MySqlPlatform());

        $this->dbal->expects($this->once())
            ->method('prepare')
            ->willReturn($deleteStatement);

        $message = new DbalMessage();
        $message->setId('message.id');

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Expected record was removed but it is not. id: "message.id"');

        $this->consumer->reject($message);
    }

    public function testRejectRequeueLogicException()
    {
        $this->queue->expects($this->once())
            ->method('getQueueName')
            ->willReturn('test_queue');

        /** @var Statement|\PHPUnit\Framework\MockObject\MockObject $statement */
        $deleteStatement = $this->createMock(Statement::class);
        $deleteStatement->expects($this->once())
            ->method('execute')
            ->with([
                'messageId' => 'message.id'
            ]);

        $deleteStatement->expects($this->once())
            ->method('rowCount')
            ->willReturn(1);

        $this->dbal->expects($this->once())
            ->method('getDatabasePlatform')
            ->willReturn(new MySqlPlatform());

        $this->dbal->expects($this->once())
            ->method('prepare')
            ->willReturn($deleteStatement);

        $dbalMessage = [
            'body' => 'message.body',
            'headers' => '{"header.key":"header.value"}',
            'properties' => '{"property.key":"property.value"}',
            'priority' => 1,
            'queue' => 'test_queue',
            'redelivered' => true,
        ];

        $this->dbal->expects($this->once())
            ->method('insert')
            ->with('oro_message_queue', $dbalMessage, [
                'body' => 'text',
                'headers' => 'text',
                'properties' => 'text',
                'priority' => 'smallint',
                'queue' => 'string',
                'redelivered' => 'boolean',
            ])
            ->willReturn(0);

        $this->connection->expects($this->exactly(2))
            ->method('getTableName')
            ->willReturn('oro_message_queue');

        $message = new DbalMessage();
        $message->setId('message.id');
        $message->setBody('message.body');
        $message->setHeaders(['header.key' => 'header.value']);
        $message->setProperties(['property.key' => 'property.value']);
        $message->setPriority(1);
        $message->setRedelivered(true);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(
            sprintf('Expected record was inserted but it is not. message: "%s"', JSON::encode($dbalMessage))
        );

        $this->consumer->reject($message, true);
    }

    public function testRejectInvalidMessageException()
    {
        $this->expectException(InvalidMessageException::class);
        $this->expectExceptionMessage(
            sprintf('The message must be an instance of %s but it is %s.', DbalMessage::class, NullMessage::class)
        );

        $this->consumer->reject(new NullMessage());
    }
}
