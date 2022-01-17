<?php

namespace Oro\Component\MessageQueue\Tests\Unit\Transport\Dbal;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use Oro\Component\MessageQueue\Transport\Dbal\DbalConnection;
use Oro\Component\MessageQueue\Transport\Dbal\DbalMessage;
use Oro\Component\MessageQueue\Transport\Dbal\DbalMessageProducer;
use Oro\Component\MessageQueue\Transport\Exception\RuntimeException;
use Oro\Component\MessageQueue\Transport\Queue;
use Oro\Component\MessageQueue\Util\JSON;

class DbalMessageProducerTest extends \PHPUnit\Framework\TestCase
{
    private DbalConnection|\PHPUnit\Framework\MockObject\MockObject $connection;

    private DbalMessageProducer $messageProducer;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->connection = $this->createMock(DbalConnection::class);
        $this->messageProducer = new DbalMessageProducer($this->connection);
    }

    public function testSend(): void
    {
        $queue = new Queue('queue name');
        $messageBody = 'message body';
        $messageProperties = ['propertyKey' => 'propertyValue'];
        $message = $this->getMessage($messageBody, $messageProperties);

        $expectedMessage = [
            'body' => JSON::encode($messageBody),
            'headers' => '[]',
            'properties' => JSON::encode($messageProperties),
            'priority' => 0,
            'queue' => 'queue name',
        ];

        $dbalConnection = $this->createMock(Connection::class);
        $dbalConnection->expects(self::once())
            ->method('insert')
            ->with('oro_message_queue', $expectedMessage, [
                'body' => Types::TEXT,
                'headers' => Types::TEXT,
                'properties' => Types::TEXT,
                'priority' => Types::SMALLINT,
                'queue' => Types::STRING,
                'delayed_until' => Types::INTEGER,
            ]);

        $this->connection
            ->expects(self::once())
            ->method('getDBALConnection')
            ->willReturn($dbalConnection);

        $this->connection
            ->expects(self::once())
            ->method('getTableName')
            ->willReturn('oro_message_queue');

        $this->messageProducer->send($queue, $message);
    }

    public function testSendWithDelay(): void
    {
        $queue = new Queue('queue name');
        $message = $this->getMessage('', [], 10);

        $dbalConnection = $this->createMock(Connection::class);
        $dbalConnection->expects(self::once())
            ->method('insert')
            ->with('oro_message_queue', self::arrayHasKey('delayed_until'), [
                'body' => Types::TEXT,
                'headers' => Types::TEXT,
                'properties' => Types::TEXT,
                'priority' => Types::SMALLINT,
                'queue' => Types::STRING,
                'delayed_until' => Types::INTEGER,
            ]);

        $this->connection
            ->expects(self::once())
            ->method('getDBALConnection')
            ->willReturn($dbalConnection);

        $this->connection
            ->expects(self::once())
            ->method('getTableName')
            ->willReturn('oro_message_queue');

        $this->messageProducer->send($queue, $message);
    }

    public function testSendRuntimeException(): void
    {
        $queue = new Queue('queue name');
        $messageBody = 'sample.message.body';
        $message = new DbalMessage();
        $message->setBody($messageBody);

        $dbalConnection = $this->createMock(Connection::class);
        $dbalConnection->expects(self::once())
            ->method('insert')
            ->with('oro_message_queue', [
                'body' => JSON::encode($messageBody),
                'headers' => '[]',
                'properties' => '[]',
                'priority' => 0,
                'queue' => 'queue name',
            ], [
                'body' => Types::TEXT,
                'headers' => Types::TEXT,
                'properties' => Types::TEXT,
                'priority' => Types::SMALLINT,
                'queue' => Types::STRING,
                'delayed_until' => Types::INTEGER,
            ])
            ->willThrowException(new \Exception());

        $this->connection
            ->expects(self::once())
            ->method('getDBALConnection')
            ->willReturn($dbalConnection);

        $this->connection
            ->expects(self::once())
            ->method('getTableName')
            ->willReturn('oro_message_queue');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The transport fails to send the message due to some internal error.');

        $this->messageProducer->send($queue, $message);
    }

    private function getMessage(string $messageBody, array $properties, int $delay = null): DbalMessage
    {
        $message = new DbalMessage();
        $message->setBody($messageBody);
        $message->setProperties($properties);

        if ($delay) {
            $message->setDelay($delay);
        }

        return $message;
    }
}
