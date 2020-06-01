<?php

namespace Oro\Bundle\MessageQueueBundle\Tests\Unit\Client;

use Oro\Bundle\MessageQueueBundle\Client\BufferedMessageProducer;
use Oro\Bundle\MessageQueueBundle\Client\ProcessorWatcher;
use Oro\Component\MessageQueue\Consumption\Context;

class ProcessorWatcherTest extends \PHPUnit\Framework\TestCase
{
    /** @var BufferedMessageProducer|\PHPUnit\Framework\MockObject\MockObject */
    private $bufferedProducer;

    /** @var ProcessorWatcher */
    private $processorWatcher;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->bufferedProducer = $this->createMock(BufferedMessageProducer::class);

        $this->processorWatcher = new ProcessorWatcher($this->bufferedProducer);
    }

    public function testShouldEnableBufferingOnPreReceivedIfBufferingIsNotEnabledYet()
    {
        $this->bufferedProducer->expects(self::once())
            ->method('isBufferingEnabled')
            ->willReturn(false);
        $this->bufferedProducer->expects(self::once())
            ->method('enableBuffering');

        $this->processorWatcher->onPreReceived($this->createMock(Context::class));
    }

    public function testShouldNotEnableBufferingOnPreReceivedIfBufferingIsAlreadyEnabled()
    {
        $this->bufferedProducer->expects(self::once())
            ->method('isBufferingEnabled')
            ->willReturn(true);
        $this->bufferedProducer->expects(self::never())
            ->method('enableBuffering');

        $this->processorWatcher->onPreReceived($this->createMock(Context::class));
    }

    public function testShouldFlushBufferOnPostReceivedIfBufferingIsEnabledAndHasMessagesInBuffer()
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

    public function testShouldNotFlushBufferOnPostReceivedIfBufferingIsEnabledButNoMessagesInBuffer()
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

    public function testShouldNotFlushBufferOnPostReceivedIfBufferingIsNotEnabled()
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
