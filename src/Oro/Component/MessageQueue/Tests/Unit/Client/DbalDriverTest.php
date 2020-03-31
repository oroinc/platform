<?php

namespace Oro\Component\MessageQueue\Tests\Unit\Client;

use Oro\Component\MessageQueue\Client\Config;
use Oro\Component\MessageQueue\Client\DbalDriver;
use Oro\Component\MessageQueue\Client\Message;
use Oro\Component\MessageQueue\Client\MessagePriority;
use Oro\Component\MessageQueue\Transport\Dbal\DbalMessage;
use Oro\Component\MessageQueue\Transport\Dbal\DbalSessionInterface;
use Oro\Component\MessageQueue\Transport\MessageProducerInterface;
use Oro\Component\MessageQueue\Transport\Queue;

class DbalDriverTest extends \PHPUnit\Framework\TestCase
{
    /** @var DbalSessionInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $session;

    /** @var DbalDriver */
    private $driver;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->session = $this->createMock(DbalSessionInterface::class);

        $config = new Config('oro', '', '', '');
        $this->driver = new DbalDriver($this->session, $config);
    }

    /**
     * @dataProvider messageDataProvider
     *
     * @param Message $message
     * @param DbalMessage $expectedTransportMessage
     */
    public function testSend(Message $message, DbalMessage $expectedTransportMessage): void
    {
        $queue = new Queue('queue name');

        $this->session
            ->expects($this->once())
            ->method('createMessage')
            ->willReturn(new DbalMessage());

        $producer = $this->createMock(MessageProducerInterface::class);
        $producer->expects($this->once())
            ->method('send')
            ->with($queue, $expectedTransportMessage);

        $this->session
            ->expects($this->once())
            ->method('createProducer')
            ->willReturn($producer);

        $this->driver->send($queue, $message);
    }

    public function testCreateQueue(): void
    {
        $queue = new Queue('queue name');

        $this->session->expects($this->once())
            ->method('createQueue')
            ->with('queue name')
            ->willReturn($queue);

        $result = $this->driver->createQueue('queue name');

        $this->assertEquals($queue, $result);
    }

    public function testCreateTransportMessage(): void
    {
        $transportMessage = $this->getTransportMessage('message id', 'message body');
        $this->session->expects($this->once())
            ->method('createMessage')
            ->willReturn($transportMessage);

        $this->assertEquals($transportMessage, $this->driver->createTransportMessage());
    }

    public function testGetConfig(): void
    {
        $config = new Config(
            'prefix',
            'message processor name',
            'router queue name',
            'default queue name',
            'default topic name'
        );

        $driver = new DbalDriver($this->session, $config);

        $this->assertEquals($config, $driver->getConfig());
    }

    /**
     * @return array
     */
    public function messageDataProvider(): array
    {
        return [
            'simple message' => [
                'message' => $this->getMessage('message id', 'message body'),
                'expectedTransportMessage' => $this->getTransportMessage(
                    'message id',
                    'message body',
                    [
                        'content_type' => 'content type',
                    ],
                    []
                )
            ],
            'message with timestamp' => [
                'message' => $this->getMessage('message id', 'message body', 3),
                'expectedTransportMessage' => $this->getTransportMessage(
                    'message id',
                    'message body',
                    [
                        'content_type' => 'content type',
                        'timestamp' => '3',
                    ],
                    []
                )
            ],
            'message with delay' => [
                'message' => $this->getMessage('message id', 'message body', null, 10),
                'expectedTransportMessage' => $this->getTransportMessage(
                    'message id',
                    'message body',
                    [
                        'content_type' => 'content type',
                    ],
                    [
                        'delay' => '10',
                    ]
                )
            ],
            'message with priority' => [
                'message' => $this->getMessage(
                    'message id',
                    'message body',
                    null,
                    null,
                    MessagePriority::VERY_HIGH
                ),
                'expectedTransportMessage' => $this->getTransportMessage(
                    'message id',
                    'message body',
                    [
                        'content_type' => 'content type',
                        'priority' => '4',
                    ],
                    []
                )
            ],
            'full message' => [
                'message' => $this->getMessage(
                    'message id',
                    'message body',
                    3,
                    10,
                    MessagePriority::VERY_HIGH
                ),
                'expectedTransportMessage' => $this->getTransportMessage(
                    'message id',
                    'message body',
                    [
                        'content_type' => 'content type',
                        'timestamp' => '3',
                        'priority' => '4',
                    ],
                    [
                        'delay' => '10',
                    ]
                )
            ],
        ];
    }

    /**
     * @param string $messageId
     * @param string $body
     * @param int|null $timestamp
     * @param int|null $delay
     * @param string|null $priority
     *
     * @return Message
     */
    private function getMessage(
        string $messageId,
        string $body,
        int $timestamp = null,
        int $delay = null,
        string $priority = null
    ): Message {
        $message = new Message($body, $priority);
        $message->setMessageId($messageId);
        $message->setTimestamp($timestamp);
        $message->setDelay($delay);
        $message->setContentType('content type');

        return $message;
    }

    /**
     * @param string $messageId
     * @param string $body
     * @param array $headers
     * @param array $properties
     *
     * @return DbalMessage
     */
    private function getTransportMessage(
        string $messageId,
        string $body,
        array $headers = [],
        array $properties = []
    ): DbalMessage {
        $message = new DbalMessage();
        $message->setBody($body);
        $message->setHeaders($headers);
        $message->setProperties($properties);
        $message->setMessageId($messageId);

        return $message;
    }
}
