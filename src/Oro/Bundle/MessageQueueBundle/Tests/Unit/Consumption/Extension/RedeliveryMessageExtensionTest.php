<?php

namespace Oro\Bundle\MessageQueueBundle\Tests\Unit\Consumption\Extension;

use Oro\Bundle\MessageQueueBundle\Consumption\Extension\RedeliveryMessageExtension;
use Oro\Component\MessageQueue\Client\DriverInterface;
use Oro\Component\MessageQueue\Client\Message;
use Oro\Component\MessageQueue\Consumption\Context;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\Message as TransportMessage;
use Oro\Component\MessageQueue\Transport\Queue;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerInterface;

class RedeliveryMessageExtensionTest extends \PHPUnit\Framework\TestCase
{
    private DriverInterface|\PHPUnit\Framework\MockObject\MockObject $driver;

    private RedeliveryMessageExtension $extension;

    protected function setUp(): void
    {
        $this->driver = $this->createMock(DriverInterface::class);

        $this->extension = new RedeliveryMessageExtension($this->driver, 10);
    }

    /**
     * @dataProvider propertiesDataProvider
     */
    public function testOnPreReceived(array $properties, array $expectedProperties): void
    {
        $session = $this->createMock(SessionInterface::class);
        $logger = $this->createMock(LoggerInterface::class);
        $message = new TransportMessage();
        $message->setBody('test body');
        $message->setHeaders(['test headers']);
        $message->setProperties($properties);
        $message->setMessageId('test message id');
        $message->setRedelivered(true);

        $queue = new Queue('oro.default');
        $context = new Context($session);
        $context->setLogger($logger);
        $context->setMessage($message);
        $context->setQueueName('oro.default');

        $session->expects(self::once())
            ->method('createQueue')
            ->with('oro.default')
            ->willReturn($queue);

        $delayedMessage = new Message('test body');
        $delayedMessage->setHeaders(['test headers', 'message_id' => 'test message id']);
        $delayedMessage->setProperties($expectedProperties);
        $delayedMessage->setDelay(10);
        $delayedMessage->setMessageId('test message id');

        $this->driver->expects(self::once())
            ->method('send')
            ->with($queue, $delayedMessage);

        $logger->expects(self::exactly(2))
            ->method('debug')
            ->willReturnMap([
                ['Send delayed message', []],
                ['Reject redelivered original message by setting reject status to context.', []],
            ]);

        $this->extension->onPreReceived($context);
        self::assertEquals(MessageProcessorInterface::REJECT, $context->getStatus());
    }

    public function testOnPreReceivedMessageIsNotRedelivered(): void
    {
        $session = $this->createMock(SessionInterface::class);
        $logger = $this->createMock(LoggerInterface::class);
        $message = new TransportMessage();

        $context = new Context($session);
        $context->setLogger($logger);
        $context->setMessage($message);

        $session->expects(self::never())
            ->method('createQueue');

        $logger->expects(self::never())
            ->method('debug');

        $this->driver->expects(self::never())
            ->method('send');

        $this->extension->onPreReceived($context);
    }

    public function propertiesDataProvider(): array
    {
        return [
            'without properties' => [
                'properties' => [],
                'expectedProperties' => ['oro-redeliver-count' => 1],
            ],
            'with extra property' => [
                'properties' => ['test properties'],
                'expectedProperties' => ['test properties', 'oro-redeliver-count' => 1],
            ],
            'with redeliver count' => [
                'properties' => ['oro-redeliver-count' => 5],
                'expectedProperties' => ['oro-redeliver-count' => 6],
            ],
        ];
    }

    public function testOnPreReceivedContextHasStatus(): void
    {
        $session = $this->createMock(SessionInterface::class);
        $logger = $this->createMock(LoggerInterface::class);
        $message = new TransportMessage();
        $message->setMessageId('sample-id');
        $message->setRedelivered(true);

        $context = new Context($session);
        $context->setLogger($logger);
        $context->setMessage($message);
        $context->setStatus(MessageProcessorInterface::REJECT);

        $session->expects(self::never())
            ->method('createQueue');

        $logger->expects(self::once())
            ->method('debug')
            ->with(
                'Skipping extension as message status is already set.',
                ['messageId' => $message->getMessageId(), 'status' => $context->getStatus()]
            );

        $this->driver->expects(self::never())
            ->method('send');

        $this->extension->onPreReceived($context);
    }
}
