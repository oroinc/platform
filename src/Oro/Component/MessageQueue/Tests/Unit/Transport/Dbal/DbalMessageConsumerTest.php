<?php

namespace Oro\Component\MessageQueue\Tests\Unit\Transport\Dbal;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\PDOException;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Statement;
use Oro\Component\MessageQueue\Transport\Dbal\DbalConnection;
use Oro\Component\MessageQueue\Transport\Dbal\DbalMessage;
use Oro\Component\MessageQueue\Transport\Dbal\DbalMessageConsumer;
use Oro\Component\MessageQueue\Transport\Dbal\DbalMessageInterface;
use Oro\Component\MessageQueue\Transport\Dbal\DbalSessionInterface;
use Oro\Component\MessageQueue\Transport\Exception\InvalidMessageException;
use Oro\Component\MessageQueue\Transport\Message;
use Oro\Component\MessageQueue\Transport\Queue;
use Oro\Component\MessageQueue\Util\JSON;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class DbalMessageConsumerTest extends \PHPUnit\Framework\TestCase
{
    /** @var DbalSessionInterface|MockObject */
    private $session;

    /** @var DbalConnection|MockObject */
    private $connection;

    /** @var Connection|MockObject */
    private $dbal;

    /** @var DbalMessageConsumer */
    private $consumer;

    protected function setUp(): void
    {
        $this->dbal = $this->createMock(Connection::class);

        $this->connection = $this->createMock(DbalConnection::class);
        $this->connection->method('getDBALConnection')->willReturn($this->dbal);

        $this->session = $this->createMock(DbalSessionInterface::class);
        $this->session->method('getConnection')->willReturn($this->connection);

        $this->consumer = new DbalMessageConsumer($this->session, new Queue('test_queue'));
    }

    public function testGetConsumerId(): void
    {
        $consumer = new DbalMessageConsumer($this->session, new Queue('test_queue'));

        static::assertNotEquals($consumer->getConsumerId(), $this->consumer->getConsumerId());
    }

    public function testPollingInterval(): void
    {
        static::assertEquals(1000, $this->consumer->getPollingInterval());

        $this->consumer->setPollingInterval(5);

        static::assertEquals(5, $this->consumer->getPollingInterval());
    }

    public function testReceiveWithMessage(): void
    {
        /** @var Statement|MockObject $statement */
        $updateStatement = $this->createMock(Statement::class);
        $updateStatement->expects(static::once())
            ->method('execute')
            ->with(static::logicalAnd(
                static::containsEqual('test_queue'),
                static::containsEqual($this->consumer->getConsumerId())
            ));
        $updateStatement->expects(static::once())->method('rowCount')->willReturn(1);

        /** @var Statement|MockObject $statement */
        $selectStatement = $this->createMock(Statement::class);
        $selectStatement->expects(static::once())
            ->method('execute')
            ->with([
                'consumerId' => $this->consumer->getConsumerId(),
                'queue' => 'test_queue',
            ]);
        $selectStatement->expects(static::once())
            ->method('fetch')
            ->with(2)
            ->willReturn([
                'id' => 25,
                'body' => 'message.body',
                'priority' => 1,
                'redelivered' => false,
                'headers' => '{"header.key":"header.value"}',
                'properties' => '{"property.key":"property.value"}',
            ]);

        $this->dbal->expects(static::once())->method('getDatabasePlatform')->willReturn(new MySqlPlatform());
        $this->dbal->expects(static::exactly(2))->method('prepare')->willReturn($updateStatement, $selectStatement);

        $this->connection->expects(static::exactly(2))->method('getTableName')->willReturn('oro_message_queue');

        $this->session->expects(static::once())->method('createMessage')->willReturn(new DbalMessage());

        $expectedMessage = new DbalMessage();
        $expectedMessage->setId(25);
        $expectedMessage->setBody('message.body');
        $expectedMessage->setPriority(1);
        $expectedMessage->setHeaders(['header.key' => 'header.value']);
        $expectedMessage->setProperties(['property.key' => 'property.value']);

        static::assertEquals($expectedMessage, $this->consumer->receive(1));
    }

    public function testReceiveWithMessageLogicException(): void
    {
        /** @var Statement|MockObject $statement */
        $updateStatement = $this->createMock(Statement::class);
        $updateStatement->expects(static::once())
            ->method('execute')
            ->with(static::logicalAnd(
                static::containsEqual('test_queue'),
                static::containsEqual($this->consumer->getConsumerId())
            ));
        $updateStatement->expects(static::once())->method('rowCount')->willReturn(1);

        /** @var Statement|MockObject $statement */
        $selectStatement = $this->createMock(Statement::class);
        $selectStatement->expects(static::once())
            ->method('execute')
            ->with([
                'consumerId' => $this->consumer->getConsumerId(),
                'queue' => 'test_queue',
            ]);
        $selectStatement->expects(static::once())->method('fetch')->with(2)->willReturn(false);

        $this->dbal->expects(static::once())->method('getDatabasePlatform')->willReturn(new MySqlPlatform());
        $this->dbal->expects(static::exactly(2))->method('prepare')->willReturn($updateStatement, $selectStatement);

        $this->connection->expects(static::exactly(2))->method('getTableName')->willReturn('oro_message_queue');

        $this->session->expects(static::never())->method('createMessage');

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(
            \sprintf('Expected one record but got nothing. consumer_id: "%s"', $this->consumer->getConsumerId())
        );

        $this->consumer->receive(1);
    }

    public function testReceiveWithoutMessage(): void
    {
        /** @var Statement|MockObject $statement */
        $updateStatement = $this->createMock(Statement::class);
        $updateStatement->expects(static::once())
            ->method('execute')
            ->with(static::logicalAnd(
                static::containsEqual('test_queue'),
                static::containsEqual($this->consumer->getConsumerId())
            ));
        $updateStatement->expects(static::once())->method('rowCount')->willReturn(0);

        $this->dbal->expects(static::once())->method('getDatabasePlatform')->willReturn(new MySqlPlatform());
        $this->dbal->expects(static::once())->method('prepare')->willReturn($updateStatement);

        $this->connection->expects(static::once())->method('getTableName')->willReturn('oro_message_queue');

        $this->session->expects(static::never())->method('createMessage');

        $this->consumer->setPollingInterval(100);

        static::assertNull($this->consumer->receive(0.1));
    }

    public function testReceiveThrowLogicException(): void
    {
        $this->dbal->expects(static::once())->method('getDatabasePlatform')->willReturn(new \stdClass());

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Unsupported database driver');

        $this->consumer->receive(1);
    }

    public function testAcknowledge(): void
    {
        /** @var Statement|MockObject $statement */
        $deleteStatement = $this->createMock(Statement::class);
        $deleteStatement->expects(static::once())->method('execute')->with(['messageId' => 25]);

        $deleteStatement->expects(static::once())->method('rowCount')->willReturn(1);

        $this->dbal->expects(static::once())->method('getDatabasePlatform')->willReturn(new MySqlPlatform());
        $this->dbal->expects(static::once())->method('prepare')->willReturn($deleteStatement);

        $message = new DbalMessage();
        $message->setId(25);

        $this->consumer->acknowledge($message);
    }

    public function testAcknowledgeWithRetry(): void
    {
        /** @var Statement|MockObject $statement */
        $deleteStatement = $this->createMock(Statement::class);
        $deleteStatement->expects(static::exactly(2))->method('execute')->with(['messageId' => 25]);

        $deleteStatement->expects(static::exactly(2))
            ->method('rowCount')
            ->willReturn(
                static::returnCallback(function () {
                    throw new PDOException(new \PDOException());
                }),
                1
            );

        $this->dbal->expects(static::once())->method('getDatabasePlatform')->willReturn(new MySqlPlatform());
        $this->dbal->expects(static::once())->method('prepare')->willReturn($deleteStatement);

        $message = new DbalMessage();
        $message->setId(25);

        $this->consumer->acknowledge($message);
    }

    public function testAcknowledgeLogicException(): void
    {
        /** @var Statement|MockObject $statement */
        $deleteStatement = $this->createMock(Statement::class);
        $deleteStatement->expects(static::once())->method('execute')->with(['messageId' => 25]);

        $deleteStatement->expects(static::once())->method('rowCount')->willReturn(0);

        $this->dbal->expects(static::once())->method('getDatabasePlatform')->willReturn(new MySqlPlatform());
        $this->dbal->expects(static::once())->method('prepare')->willReturn($deleteStatement);

        $message = new DbalMessage();
        $message->setId(25);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Expected record was removed but it is not. id: "25"');

        $this->consumer->acknowledge($message);
    }

    public function testAcknowledgeInvalidMessageException(): void
    {
        $this->expectException(InvalidMessageException::class);
        $this->expectExceptionMessage(
            sprintf('The transport message must be instance of "%s".', DbalMessageInterface::class)
        );

        $this->consumer->acknowledge(new Message());
    }

    public function testReject(): void
    {
        /** @var Statement|MockObject $statement */
        $deleteStatement = $this->createMock(Statement::class);
        $deleteStatement->expects(static::once())->method('execute')->with(['messageId' => 25]);
        $deleteStatement->expects(static::once())->method('rowCount')->willReturn(1);

        $this->dbal->expects(static::once())->method('getDatabasePlatform')->willReturn(new MySqlPlatform());
        $this->dbal->expects(static::once())->method('prepare')->willReturn($deleteStatement);

        $message = new DbalMessage();
        $message->setId(25);

        $this->consumer->reject($message);
    }

    public function testRejectWithRetry(): void
    {
        /** @var Statement|MockObject $statement */
        $deleteStatement = $this->createMock(Statement::class);
        $deleteStatement->expects(static::exactly(2))->method('execute')->with(['messageId' => 25]);

        $deleteStatement->expects(static::exactly(2))
            ->method('rowCount')
            ->willReturn(
                static::returnCallback(function () {
                    throw new PDOException(new \PDOException());
                }),
                1
            );

        $this->dbal->expects(static::once())->method('getDatabasePlatform')->willReturn(new MySqlPlatform());
        $this->dbal->expects(static::once())->method('prepare')->willReturn($deleteStatement);

        $message = new DbalMessage();
        $message->setId(25);

        $this->consumer->reject($message);
    }

    public function testRejectRequeue(): void
    {
        /** @var Statement|MockObject $statement */
        $deleteStatement = $this->createMock(Statement::class);
        $deleteStatement->expects(static::once())->method('execute')->with(['messageId' => 25]);

        $deleteStatement->expects(static::once())->method('rowCount')->willReturn(1);

        $this->dbal->expects(static::once())->method('getDatabasePlatform')->willReturn(new MySqlPlatform());
        $this->dbal->expects(static::once())->method('prepare')->willReturn($deleteStatement);
        $this->dbal->expects(static::once())
            ->method('insert')
            ->with('oro_message_queue', [
                'body' => 'message.body',
                'headers' => '{"header.key":"header.value","priority":"1"}',
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

        $this->connection->expects(static::exactly(2))->method('getTableName')->willReturn('oro_message_queue');

        $message = new DbalMessage();
        $message->setId(25);
        $message->setBody('message.body');
        $message->setHeaders(['header.key' => 'header.value']);
        $message->setProperties(['property.key' => 'property.value']);
        $message->setPriority(1);
        $message->setRedelivered(true);

        $this->consumer->reject($message, true);
    }

    public function testRejectLogicException(): void
    {
        /** @var Statement|MockObject $statement */
        $deleteStatement = $this->createMock(Statement::class);
        $deleteStatement->expects(static::once())->method('execute')->with(['messageId' => 25]);
        $deleteStatement->expects(static::once())->method('rowCount')->willReturn(0);

        $this->dbal->expects(static::once())->method('getDatabasePlatform')->willReturn(new MySqlPlatform());
        $this->dbal->expects(static::once())->method('prepare')->willReturn($deleteStatement);

        $message = new DbalMessage();
        $message->setId(25);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Expected record was removed but it is not. id: "25"');

        $this->consumer->reject($message);
    }

    public function testRejectRequeueLogicException(): void
    {
        /** @var Statement|MockObject $statement */
        $deleteStatement = $this->createMock(Statement::class);
        $deleteStatement->expects(static::once())->method('execute')->with(['messageId' => 25]);
        $deleteStatement->expects(static::once())->method('rowCount')->willReturn(1);

        $this->dbal->expects(static::once())->method('getDatabasePlatform')->willReturn(new MySqlPlatform());
        $this->dbal->expects(static::once())->method('prepare')->willReturn($deleteStatement);

        $dbalMessage = [
            'body' => 'message.body',
            'headers' => '{"header.key":"header.value","priority":"1"}',
            'properties' => '{"property.key":"property.value"}',
            'priority' => 1,
            'queue' => 'test_queue',
            'redelivered' => true,
        ];

        $this->dbal->expects(static::once())
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

        $this->connection->expects(static::exactly(2))->method('getTableName')->willReturn('oro_message_queue');

        $message = new DbalMessage();
        $message->setId(25);
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

    public function testRejectInvalidMessageException(): void
    {
        $this->expectException(InvalidMessageException::class);
        $this->expectExceptionMessage(
            sprintf('The transport message must be instance of "%s".', DbalMessageInterface::class)
        );

        $this->consumer->reject(new Message());
    }
}
