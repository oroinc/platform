<?php

namespace Oro\Bundle\MessageQueueBundle\Tests\Unit\Client;

use Oro\Bundle\MessageQueueBundle\Client\BufferedMessageProducer;
use Oro\Bundle\MessageQueueBundle\Client\ProcessorWatcher;
use Oro\Component\MessageQueue\Consumption\Context;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ProcessorWatcherTest extends TestCase
{
    private BufferedMessageProducer&MockObject $bufferedProducer;
    private ProcessorWatcher $processorWatcher;

    #[\Override]
    protected function setUp(): void
    {
        $this->bufferedProducer = $this->createMock(BufferedMessageProducer::class);

        $this->processorWatcher = new ProcessorWatcher($this->bufferedProducer);
    }

    public function testShouldEnableBufferingOnPreReceivedIfBufferingIsNotEnabledYet(): void
    {
        $this->bufferedProducer->expects(self::once())
            ->method('isBufferingEnabled')
            ->willReturn(false);
        $this->bufferedProducer->expects(self::once())
            ->method('enableBuffering');

        $this->processorWatcher->onPreReceived($this->createMock(Context::class));
    }

    public function testShouldNotEnableBufferingOnPreReceivedIfBufferingIsAlreadyEnabled(): void
    {
        $this->bufferedProducer->expects(self::once())
            ->method('isBufferingEnabled')
            ->willReturn(true);
        $this->bufferedProducer->expects(self::never())
            ->method('enableBuffering');

        $this->processorWatcher->onPreReceived($this->createMock(Context::class));
    }

    public function testShouldFlushBufferOnPostReceivedIfBufferingIsEnabledAndHasMessagesInBuffer(): void
    {
        $this->bufferedProducer->expects(self::once())
            ->method('isBufferingEnabled')
            ->willReturn(true);
        $this->bufferedProducer->expects(self::once())
            ->method('hasBufferedMessages')
            ->willReturn(true);
        $this->bufferedProducer->expects(self::once())
            ->method('flushBuffer');

        $this->processorWatcher->onPostReceived($this->createMock(Context::class));
    }

    public function testShouldNotFlushBufferOnPostReceivedIfBufferingIsEnabledButNoMessagesInBuffer(): void
    {
        $this->bufferedProducer->expects(self::once())
            ->method('isBufferingEnabled')
            ->willReturn(true);
        $this->bufferedProducer->expects(self::once())
            ->method('hasBufferedMessages')
            ->willReturn(false);
        $this->bufferedProducer->expects(self::never())
            ->method('flushBuffer');

        $this->processorWatcher->onPostReceived($this->createMock(Context::class));
    }

    public function testShouldNotFlushBufferOnPostReceivedIfBufferingIsNotEnabled(): void
    {
        $this->bufferedProducer->expects(self::once())
            ->method('isBufferingEnabled')
            ->willReturn(false);
        $this->bufferedProducer->expects(self::never())
            ->method('hasBufferedMessages');
        $this->bufferedProducer->expects(self::never())
            ->method('flushBuffer');

        $this->processorWatcher->onPostReceived($this->createMock(Context::class));
    }
}
