<?php

namespace Oro\Bundle\MessageQueueBundle\Tests\Unit\Test;

use Oro\Bundle\MessageQueueBundle\Test\MessageCollector;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class MessageCollectorTest extends TestCase
{
    private MessageProducerInterface&MockObject $messageProducer;
    private MessageCollector $messageCollector;

    #[\Override]
    protected function setUp(): void
    {
        $this->messageProducer = $this->createMock(MessageProducerInterface::class);

        $this->messageCollector = new MessageCollector($this->messageProducer);
    }

    public function testShouldCallInternalMessageProducerSendMethod(): void
    {
        $topic = 'test topic';
        $message = 'test message';

        $this->messageProducer->expects($this->once())
            ->method('send')
            ->with($topic, $message);

        $this->messageCollector->send($topic, $message);
    }

    public function testShouldCollectMessages(): void
    {
        $topic = 'test topic';
        $message = 'test message';

        $this->messageCollector->send($topic, $message);

        self::assertEquals(
            [
                ['topic' => $topic, 'message' => $message]
            ],
            $this->messageCollector->getSentMessages()
        );
    }

    public function testShouldAllowClearCollectedMessages(): void
    {
        $this->messageCollector->send('test topic', 'test message');
        $this->messageCollector->clear();

        self::assertEquals([], $this->messageCollector->getSentMessages());
    }

    public function testShouldAllowClearCollectedTopicMessages(): void
    {
        $this->messageCollector->send('test topic 1', 'test message 1');
        $this->messageCollector->send('test topic 2', 'test message 2');
        $this->messageCollector->clearTopicMessages('test topic 1');

        self::assertEquals(
            [['topic' => 'test topic 2', 'message' => 'test message 2']],
            $this->messageCollector->getSentMessages()
        );
    }

    public function testShouldNotCatchExceptionFromInternalMessageProducer(): void
    {
        $exception = new \Exception('some error');

        $this->messageProducer->expects($this->once())
            ->method('send')
            ->willThrowException($exception);

        $this->expectException(get_class($exception));
        $this->expectExceptionMessage($exception->getMessage());
        $this->messageCollector->send('test topic', 'test message');
    }

    public function testShouldNotStoreMessageIfInternalMessageProducerThrowsException(): void
    {
        $this->messageProducer->expects($this->once())
            ->method('send')
            ->willThrowException(new \Exception());

        try {
            $this->messageCollector->send('test topic', 'test message');
        } catch (\Exception $e) {
            self::assertEquals([], $this->messageCollector->getSentMessages());
        }
    }

    public function testShouldBePossibleToUseWithoutInternalMessageProducer(): void
    {
        $topic = 'test topic';
        $message = 'test message';

        $messageCollector = new MessageCollector();
        $messageCollector->send($topic, $message);

        self::assertEquals(
            [
                ['topic' => $topic, 'message' => $message]
            ],
            $messageCollector->getSentMessages()
        );
    }
}
