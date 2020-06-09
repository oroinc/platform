<?php

namespace Oro\Component\MessageQueue\Tests\Unit\Transport\Dbal;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Type;
use Oro\Component\MessageQueue\Transport\Dbal\DbalConnection;
use Oro\Component\MessageQueue\Transport\Dbal\DbalMessage;
use Oro\Component\MessageQueue\Transport\Dbal\DbalMessageProducer;
use Oro\Component\MessageQueue\Transport\Exception\RuntimeException;
use Oro\Component\MessageQueue\Transport\Queue;

class DbalMessageProducerTest extends \PHPUnit\Framework\TestCase
{
    /** @var DbalConnection|\PHPUnit\Framework\MockObject\MockObject */
    private $connection;

    /** @var DbalMessageProducer */
    private $messageProducer;

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
        $message = $this->getMessage('message body', [
            'propertyKey' => 'propertyValue'
        ]);

        $expectedMessage = [
            'body' => 'message body',
            'headers' => '[]',
            'properties' => '{"propertyKey":"propertyValue"}',
            'priority' => 0,
            'queue' => 'queue name',
        ];

        $dbalConnection = $this->createMock(Connection::class);
        $dbalConnection->expects($this->once())
            ->method('insert')
            ->with('oro_message_queue', $expectedMessage, [
                'body' => Type::TEXT,
                'headers' => Type::TEXT,
                'properties' => Type::TEXT,
                'priority' => Type::SMALLINT,
                'queue' => Type::STRING,
                'delayed_until' => Type::INTEGER,
            ]);

        $this->connection
            ->expects($this->once())
            ->method('getDBALConnection')
            ->willReturn($dbalConnection);

        $this->connection
            ->expects($this->once())
            ->method('getTableName')
            ->willReturn('oro_message_queue');

        $this->messageProducer->send($queue, $message);
    }

    public function testSendWithDelay(): void
    {
        $queue = new Queue('queue name');
        $message = $this->getMessage('', [], 10);

        $dbalConnection = $this->createMock(Connection::class);
        $dbalConnection->expects($this->once())
            ->method('insert')
            ->with('oro_message_queue', $this->arrayHasKey('delayed_until'), [
                'body' => Type::TEXT,
                'headers' => Type::TEXT,
                'properties' => Type::TEXT,
                'priority' => Type::SMALLINT,
                'queue' => Type::STRING,
                'delayed_until' => Type::INTEGER,
            ]);

        $this->connection
            ->expects($this->once())
            ->method('getDBALConnection')
            ->willReturn($dbalConnection);

        $this->connection
            ->expects($this->once())
            ->method('getTableName')
            ->willReturn('oro_message_queue');

        $this->messageProducer->send($queue, $message);
    }

    public function testSendRuntimeException(): void
    {
        $queue = new Queue('queue name');
        $message = new DbalMessage();

        $dbalConnection = $this->createMock(Connection::class);
        $dbalConnection->expects($this->once())
            ->method('insert')
            ->with('oro_message_queue', [
                'body' => '',
                'headers' => '[]',
                'properties' => '[]',
                'priority' => 0,
                'queue' => 'queue name',
            ], [
                'body' => Type::TEXT,
                'headers' => Type::TEXT,
                'properties' => Type::TEXT,
                'priority' => Type::SMALLINT,
                'queue' => Type::STRING,
                'delayed_until' => Type::INTEGER,
            ])
            ->willThrowException(new \Exception());

        $this->connection
            ->expects($this->once())
            ->method('getDBALConnection')
            ->willReturn($dbalConnection);

        $this->connection
            ->expects($this->once())
            ->method('getTableName')
            ->willReturn('oro_message_queue');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The transport fails to send the message due to some internal error.');

        $this->messageProducer->send($queue, $message);
    }

    /**
     * @param string $messageBody
     * @param array $properties
     * @param int $delay
     *
     * @return DbalMessage
     */
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
