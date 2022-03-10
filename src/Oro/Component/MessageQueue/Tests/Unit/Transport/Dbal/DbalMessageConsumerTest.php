<?php

namespace Oro\Component\MessageQueue\Tests\Unit\Transport\Dbal;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\PDOException;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Statement;
use Doctrine\DBAL\Types\Types;
use Oro\Component\MessageQueue\Transport\Dbal\DbalConnection;
use Oro\Component\MessageQueue\Transport\Dbal\DbalMessage;
use Oro\Component\MessageQueue\Transport\Dbal\DbalMessageConsumer;
use Oro\Component\MessageQueue\Transport\Dbal\DbalMessageInterface;
use Oro\Component\MessageQueue\Transport\Dbal\DbalSessionInterface;
use Oro\Component\MessageQueue\Transport\Exception\InvalidMessageException;
use Oro\Component\MessageQueue\Transport\Exception\RuntimeException;
use Oro\Component\MessageQueue\Transport\Message;
use Oro\Component\MessageQueue\Transport\Queue;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class DbalMessageConsumerTest extends \PHPUnit\Framework\TestCase
{
    private DbalSessionInterface|\PHPUnit\Framework\MockObject\MockObject $session;

    private DbalConnection|\PHPUnit\Framework\MockObject\MockObject $connection;

    private Connection|\PHPUnit\Framework\MockObject\MockObject $dbal;

    private DbalMessageConsumer $consumer;

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

        self::assertNotEquals($consumer->getConsumerId(), $this->consumer->getConsumerId());
    }

    public function testPollingInterval(): void
    {
        self::assertEquals(1000, $this->consumer->getPollingInterval());

        $this->consumer->setPollingInterval(5);

        self::assertEquals(5, $this->consumer->getPollingInterval());
    }

    public function testReceiveWithMessage(): void
    {
        /** @var Statement|MockObject $statement */
        $updateStatement = $this->createMock(Statement::class);
        $updateStatement->expects(self::once())
            ->method('execute')
            ->with(
                self::logicalAnd(
                    self::containsEqual('test_queue'),
                    self::containsEqual($this->consumer->getConsumerId())
                )
            );
        $updateStatement->expects(self::once())->method('rowCount')->willReturn(1);

        /** @var Statement|MockObject $statement */
        $selectStatement = $this->createMock(Statement::class);
        $selectStatement->expects(self::once())
            ->method('execute')
            ->with([
                'consumerId' => $this->consumer->getConsumerId(),
                'queue' => 'test_queue',
            ]);
        $selectStatement->expects(self::once())
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

        $this->dbal->expects(self::once())->method('getDatabasePlatform')->willReturn(new MySqlPlatform());
        $this->dbal->expects(self::exactly(2))->method('prepare')->willReturn($updateStatement, $selectStatement);

        $this->connection->expects(self::exactly(2))->method('getTableName')->willReturn('oro_message_queue');

        $this->session->expects(self::once())->method('createMessage')->willReturn(new DbalMessage());

        $expectedMessage = new DbalMessage();
        $expectedMessage->setId(25);
        $expectedMessage->setBody('message.body');
        $expectedMessage->setPriority(1);
        $expectedMessage->setHeaders(['header.key' => 'header.value']);
        $expectedMessage->setProperties(['property.key' => 'property.value']);

        self::assertEquals($expectedMessage, $this->consumer->receive(1));
    }

    public function testReceiveWithMessageLogicException(): void
    {
        /** @var Statement|MockObject $statement */
        $updateStatement = $this->createMock(Statement::class);
        $updateStatement->expects(self::once())
            ->method('execute')
            ->with(
                self::logicalAnd(
                    self::containsEqual('test_queue'),
                    self::containsEqual($this->consumer->getConsumerId())
                )
            );
        $updateStatement->expects(self::once())->method('rowCount')->willReturn(1);

        /** @var Statement|MockObject $statement */
        $selectStatement = $this->createMock(Statement::class);
        $selectStatement->expects(self::once())
            ->method('execute')
            ->with([
                'consumerId' => $this->consumer->getConsumerId(),
                'queue' => 'test_queue',
            ]);
        $selectStatement->expects(self::once())->method('fetch')->with(2)->willReturn(false);

        $this->dbal->expects(self::once())->method('getDatabasePlatform')->willReturn(new MySqlPlatform());
        $this->dbal->expects(self::exactly(2))->method('prepare')->willReturn($updateStatement, $selectStatement);

        $this->connection->expects(self::exactly(2))->method('getTableName')->willReturn('oro_message_queue');

        $this->session->expects(self::never())->method('createMessage');

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
        $updateStatement->expects(self::once())
            ->method('execute')
            ->with(
                self::logicalAnd(
                    self::containsEqual('test_queue'),
                    self::containsEqual($this->consumer->getConsumerId())
                )
            );
        $updateStatement->expects(self::once())->method('rowCount')->willReturn(0);

        $this->dbal->expects(self::once())->method('getDatabasePlatform')->willReturn(new MySqlPlatform());
        $this->dbal->expects(self::once())->method('prepare')->willReturn($updateStatement);

        $this->connection->expects(self::once())->method('getTableName')->willReturn('oro_message_queue');

        $this->session->expects(self::never())->method('createMessage');

        $this->consumer->setPollingInterval(100);

        self::assertNull($this->consumer->receive(0.1));
    }

    public function testReceiveThrowLogicException(): void
    {
        $this->dbal->expects(self::once())->method('getDatabasePlatform')->willReturn(new \stdClass());

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Unsupported database driver');

        $this->consumer->receive(1);
    }

    public function testAcknowledge(): void
    {
        /** @var Statement|MockObject $statement */
        $deleteStatement = $this->createMock(Statement::class);
        $deleteStatement->expects(self::once())->method('execute')->with(['messageId' => 25]);

        $deleteStatement->expects(self::once())->method('rowCount')->willReturn(1);

        $this->dbal->expects(self::once())->method('getDatabasePlatform')->willReturn(new MySqlPlatform());
        $this->dbal->expects(self::once())->method('prepare')->willReturn($deleteStatement);

        $message = new DbalMessage();
        $message->setId(25);

        $this->consumer->acknowledge($message);
    }

    public function testAcknowledgeWithRetry(): void
    {
        /** @var Statement|MockObject $statement */
        $deleteStatement = $this->createMock(Statement::class);
        $deleteStatement->expects(self::exactly(2))->method('execute')->with(['messageId' => 25]);

        $deleteStatement->expects(self::exactly(2))
            ->method('rowCount')
            ->willReturn(
                self::returnCallback(function () {
                    throw new PDOException(new \PDOException());
                }),
                1
            );

        $this->dbal->expects(self::once())->method('getDatabasePlatform')->willReturn(new MySqlPlatform());
        $this->dbal->expects(self::once())->method('prepare')->willReturn($deleteStatement);

        $message = new DbalMessage();
        $message->setId(25);

        $this->consumer->acknowledge($message);
    }

    public function testAcknowledgeLogicException(): void
    {
        /** @var Statement|MockObject $statement */
        $deleteStatement = $this->createMock(Statement::class);
        $deleteStatement->expects(self::once())->method('execute')->with(['messageId' => 25]);

        $deleteStatement->expects(self::once())->method('rowCount')->willReturn(0);

        $this->dbal->expects(self::once())->method('getDatabasePlatform')->willReturn(new MySqlPlatform());
        $this->dbal->expects(self::once())->method('prepare')->willReturn($deleteStatement);

        $message = new DbalMessage();
        $message->setId(25);

        $this->expectExceptionObject(
            new RuntimeException('Failed to delete a message with id "25". Expected 1 affected row, got 0.')
        );

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
        $deleteStatement->expects(self::once())->method('execute')->with(['messageId' => 25]);
        $deleteStatement->expects(self::once())->method('rowCount')->willReturn(1);

        $this->dbal->expects(self::once())->method('getDatabasePlatform')->willReturn(new MySqlPlatform());
        $this->dbal->expects(self::once())->method('prepare')->willReturn($deleteStatement);

        $message = new DbalMessage();
        $message->setId(25);

        $this->consumer->reject($message);
    }

    public function testRejectWithRetry(): void
    {
        /** @var Statement|MockObject $statement */
        $deleteStatement = $this->createMock(Statement::class);
        $deleteStatement->expects(self::exactly(2))->method('execute')->with(['messageId' => 25]);

        $deleteStatement->expects(self::exactly(2))
            ->method('rowCount')
            ->willReturn(
                self::returnCallback(function () {
                    throw new PDOException(new \PDOException());
                }),
                1
            );

        $this->dbal->expects(self::once())->method('getDatabasePlatform')->willReturn(new MySqlPlatform());
        $this->dbal->expects(self::once())->method('prepare')->willReturn($deleteStatement);

        $message = new DbalMessage();
        $message->setId(25);

        $this->consumer->reject($message);
    }

    public function testRejectRequeue(): void
    {
        $this->dbal
            ->expects(self::once())
            ->method('update')
            ->with(
                'oro_message_queue',
                [
                    'consumer_id' => null,
                    'redelivered' => true,
                ],
                ['id' => 25],
                [
                    'id' => Types::INTEGER,
                    'redelivered' => Types::BOOLEAN,
                ]
            )
            ->willReturn(1);

        $this->connection
            ->expects(self::once())
            ->method('getTableName')
            ->willReturn('oro_message_queue');

        $message = new DbalMessage();
        $message->setId(25);

        $this->consumer->reject($message, true);
    }

    public function testRejectThrowsExceptionWhenNoAffectedRows(): void
    {
        /** @var Statement|MockObject $statement */
        $deleteStatement = $this->createMock(Statement::class);
        $deleteStatement->expects(self::once())->method('execute')->with(['messageId' => 25]);
        $deleteStatement->expects(self::once())->method('rowCount')->willReturn(0);

        $this->dbal->expects(self::once())->method('getDatabasePlatform')->willReturn(new MySqlPlatform());
        $this->dbal->expects(self::once())->method('prepare')->willReturn($deleteStatement);

        $message = new DbalMessage();
        $message->setId(25);

        $this->expectExceptionObject(
            new RuntimeException('Failed to delete a message with id "25". Expected 1 affected row, got 0.')
        );

        $this->consumer->reject($message);
    }

    public function testRejectRequeueThrowsExceptionWhenNoAffectedRows(): void
    {
        $this->dbal
            ->expects(self::once())
            ->method('update')
            ->with(
                'oro_message_queue',
                [
                    'consumer_id' => null,
                    'redelivered' => true,
                ],
                ['id' => 25],
                [
                    'id' => Types::INTEGER,
                    'redelivered' => Types::BOOLEAN,
                ]
            )
            ->willReturn(0);

        $this->connection
            ->expects(self::once())
            ->method('getTableName')
            ->willReturn('oro_message_queue');

        $message = new DbalMessage();
        $message->setId(25);

        $this->expectExceptionObject(
            new RuntimeException('Failed to requeue a message with id "25". Expected 1 affected row, got 0.')
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
