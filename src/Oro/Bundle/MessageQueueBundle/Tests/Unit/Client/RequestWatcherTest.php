<?php

namespace Oro\Bundle\MessageQueueBundle\Tests\Unit\Client;

use Oro\Bundle\MessageQueueBundle\Client\BufferedMessageProducer;
use Oro\Bundle\MessageQueueBundle\Client\RequestWatcher;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\TerminateEvent;

class RequestWatcherTest extends \PHPUnit\Framework\TestCase
{
    /** @var BufferedMessageProducer|\PHPUnit\Framework\MockObject\MockObject */
    private $bufferedProducer;

    /** @var RequestWatcher */
    private $requestWatcher;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->bufferedProducer = $this->createMock(BufferedMessageProducer::class);
        $this->requestWatcher = new RequestWatcher($this->bufferedProducer);
    }

    public function testShouldDoNothingOnRequestStartForNotMasterRequest()
    {
        $event = $this->createMock(RequestEvent::class);
        $event->expects(self::once())
            ->method('isMasterRequest')
            ->willReturn(false);
        $this->bufferedProducer->expects(self::never())
            ->method('isBufferingEnabled');
        $this->bufferedProducer->expects(self::never())
            ->method('enableBuffering');

        $this->requestWatcher->onRequestStart($event);
    }

    public function testShouldEnableBufferingOnRequestStartIfBufferingIsNotEnabledYet()
    {
        $event = $this->createMock(RequestEvent::class);
        $event->expects(self::once())
            ->method('isMasterRequest')
            ->willReturn(true);
        $this->bufferedProducer->expects(self::once())
            ->method('isBufferingEnabled')
            ->willReturn(false);
        $this->bufferedProducer->expects(self::once())
            ->method('enableBuffering');

        $this->requestWatcher->onRequestStart($event);
    }

    public function testShouldNotEnableBufferingOnRequestStartIfBufferingIsAlreadyEnabled()
    {
        $event = $this->createMock(RequestEvent::class);
        $event->expects(self::once())
            ->method('isMasterRequest')
            ->willReturn(true);
        $this->bufferedProducer->expects(self::once())
            ->method('isBufferingEnabled')
            ->willReturn(true);
        $this->bufferedProducer->expects(self::never())
            ->method('enableBuffering');

        $this->requestWatcher->onRequestStart($event);
    }

    public function testShouldDoNothingOnRequestEndForNotMasterRequest()
    {
        $event = $this->createMock(TerminateEvent::class);
        $event->expects(self::once())
            ->method('isMasterRequest')
            ->willReturn(false);
        $this->bufferedProducer->expects(self::never())
            ->method('isBufferingEnabled');
        $this->bufferedProducer->expects(self::never())
            ->method('hasBufferedMessages');
        $this->bufferedProducer->expects(self::never())
            ->method('flushBuffer');

        $this->requestWatcher->onRequestEnd($event);
    }

    public function testShouldFlushBufferOnRequestEndIfBufferingIsEnabledAndHasMessagesInBuffer()
    {
        $event = $this->createMock(TerminateEvent::class);
        $event->expects(self::once())
            ->method('isMasterRequest')
            ->willReturn(true);
        $this->bufferedProducer->expects(self::once())
            ->method('isBufferingEnabled')
            ->willReturn(true);
        $this->bufferedProducer->expects(self::once())
            ->method('hasBufferedMessages')
            ->willReturn(true);
        $this->bufferedProducer->expects(self::once())
            ->method('flushBuffer');

        $this->requestWatcher->onRequestEnd($event);
    }

    public function testShouldNotFlushBufferOnRequestEndIfBufferingIsEnabledButNoMessagesInBuffer()
    {
        $event = $this->createMock(TerminateEvent::class);
        $event->expects(self::once())
            ->method('isMasterRequest')
            ->willReturn(true);
        $this->bufferedProducer->expects(self::once())
            ->method('isBufferingEnabled')
            ->willReturn(true);
        $this->bufferedProducer->expects(self::once())
            ->method('hasBufferedMessages')
            ->willReturn(false);
        $this->bufferedProducer->expects(self::never())
            ->method('flushBuffer');

        $this->requestWatcher->onRequestEnd($event);
    }

    public function testShouldNotFlushBufferOnRequestEndIfBufferingIsNotEnabled()
    {
        $event = $this->createMock(TerminateEvent::class);
        $event->expects(self::once())
            ->method('isMasterRequest')
            ->willReturn(true);
        $this->bufferedProducer->expects(self::once())
            ->method('isBufferingEnabled')
            ->willReturn(false);
        $this->bufferedProducer->expects(self::never())
            ->method('hasBufferedMessages');
        $this->bufferedProducer->expects(self::never())
            ->method('flushBuffer');

        $this->requestWatcher->onRequestEnd($event);
    }
}
