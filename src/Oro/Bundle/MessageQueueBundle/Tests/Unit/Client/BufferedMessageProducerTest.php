<?php

namespace Oro\Bundle\MessageQueueBundle\Tests\Unit\Client;

use Oro\Bundle\MessageQueueBundle\Client\BufferedMessageProducer;
use Oro\Bundle\MessageQueueBundle\Client\MessageBuffer;
use Oro\Bundle\MessageQueueBundle\Client\MessageFilterInterface;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Transport\Exception\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class BufferedMessageProducerTest extends TestCase
{
    private MessageProducerInterface&MockObject $inner;
    private LoggerInterface&MockObject $logger;
    private MessageFilterInterface&MockObject $messageFilter;
    private BufferedMessageProducer $producer;

    #[\Override]
    protected function setUp(): void
    {
        $this->inner = $this->createMock(MessageProducerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->messageFilter = $this->createMock(MessageFilterInterface::class);

        $this->producer = new BufferedMessageProducer($this->inner, $this->logger, $this->messageFilter);
    }

    public function testBufferDisabledByDefault(): void
    {
        self::assertFalse($this->producer->isBufferingEnabled());
    }

    public function testEnableBuffering(): void
    {
        $this->producer->enableBuffering();
        self::assertTrue($this->producer->isBufferingEnabled());
    }

    public function testDisableBuffering(): void
    {
        $this->producer->enableBuffering();
        $this->producer->disableBuffering();
        self::assertFalse($this->producer->isBufferingEnabled());
    }

    public function testDisableBufferingWhenItIsAlreadyDisabled(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The buffering of messages is already disabled.');

        $this->logger->expects(self::once())
            ->method('critical')
            ->with('The buffered message producer fails because the buffering of messages is already disabled.');

        $this->producer->disableBuffering();
    }

    public function testEnableBufferingNestingLevel(): void
    {
        self::assertFalse($this->producer->isBufferingEnabled());

        $this->producer->enableBuffering();
        self::assertTrue($this->producer->isBufferingEnabled());

        $this->producer->enableBuffering();
        self::assertTrue($this->producer->isBufferingEnabled());

        $this->producer->disableBuffering();
        self::assertTrue($this->producer->isBufferingEnabled());

        $this->producer->disableBuffering();
        self::assertFalse($this->producer->isBufferingEnabled());
    }

    public function testSendWhenBufferingIsDisabled(): void
    {
        $topic = 'test1';
        $message = ['test_data'];

        $this->messageFilter->expects(self::once())
            ->method('apply')
            ->willReturnCallback(function (MessageBuffer $buffer) use ($topic, $message) {
                self::assertEquals([[$topic, $message]], $buffer->getMessages());
            });
        $this->inner->expects(self::once())
            ->method('send')
            ->with($topic, $message);

        $this->producer->send($topic, $message);
    }

    public function testHasBufferedMessages(): void
    {
        $this->producer->enableBuffering();

        self::assertFalse($this->producer->hasBufferedMessages());

        $this->producer->send('test', ['test_data']);
        self::assertTrue($this->producer->hasBufferedMessages());

        $this->producer->flushBuffer();
        self::assertFalse($this->producer->hasBufferedMessages());

        $this->producer->send('test', ['test_data']);
        self::assertTrue($this->producer->hasBufferedMessages());

        $this->producer->clearBuffer();
        self::assertFalse($this->producer->hasBufferedMessages());
    }

    public function testClearBuffer(): void
    {
        // send several messages to fill the buffer
        $messages = [
            ['test1', ['test_data']],
            ['test2', [new \stdClass()]]
        ];
        $this->producer->enableBuffering();
        foreach ($messages as [$topic, $message]) {
            $this->producer->send($topic, $message);
        }

        $this->inner->expects(self::never())
            ->method('send');

        // do the test
        $this->producer->clearBuffer();
        // test that the buffer is cleared up
        $this->producer->flushBuffer();
    }

    public function testFlushBuffer(): void
    {
        // send several messages to fill the buffer
        $messages = [
            ['test1', ['test_data']],
            ['test2', [new \stdClass()]],
            ['test3', ['to_be_filtered']]
        ];
        $this->producer->enableBuffering();
        foreach ($messages as [$topic, $message]) {
            $this->producer->send($topic, $message);
        }

        $this->messageFilter->expects(self::once())
            ->method('apply')
            ->willReturnCallback(function (MessageBuffer $buffer) {
                foreach ($buffer->getMessagesForTopic('test3') as $messageId => $message) {
                    $buffer->removeMessage($messageId);
                }
            });

        // do the test
        $this->inner->expects(self::exactly(2))
            ->method('send')
            ->withConsecutive($messages[0], $messages[1]);

        $this->producer->flushBuffer();
        // test that the buffer is cleared up
        $this->producer->flushBuffer();
    }

    public function testFlushBufferWhenInnerProducerThrowsException(): void
    {
        $exception = new Exception('some error');

        $this->expectException(get_class($exception));
        $this->expectExceptionMessage($exception->getMessage());

        $this->producer->enableBuffering();
        $this->producer->send('test1', ['test_data']);

        $this->messageFilter->expects(self::once())
            ->method('apply');

        $this->inner->expects(self::once())
            ->method('send')
            ->willThrowException($exception);

        $this->logger->expects(self::once())
            ->method('error')
            ->with(
                'The buffered message producer fails to send messages to the queue.',
                ['exception' => $exception]
            );

        $this->producer->flushBuffer();
    }

    public function testDisableBufferingThrowExceptionOnFlush(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The buffering of messages is disabled.');

        $this->logger->expects(self::once())
            ->method('critical')
            ->with('The buffered message producer fails because the buffering of messages is disabled.');

        $this->producer->flushBuffer();
    }

    public function testDisableBufferingThrowExceptionOnClear(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The buffering of messages is disabled.');

        $this->logger->expects(self::once())
            ->method('critical')
            ->with('The buffered message producer fails because the buffering of messages is disabled.');

        $this->producer->clearBuffer();
    }
}
