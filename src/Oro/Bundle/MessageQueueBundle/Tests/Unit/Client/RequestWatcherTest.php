<?php

namespace Oro\Bundle\MessageQueueBundle\Tests\Unit\Client;

use Oro\Bundle\MessageQueueBundle\Client\BufferedMessageProducer;
use Oro\Bundle\MessageQueueBundle\Client\RequestWatcher;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class RequestWatcherTest extends TestCase
{
    private BufferedMessageProducer&MockObject $bufferedProducer;
    private RequestWatcher $requestWatcher;

    #[\Override]
    protected function setUp(): void
    {
        $this->bufferedProducer = $this->createMock(BufferedMessageProducer::class);
        $this->requestWatcher = new RequestWatcher($this->bufferedProducer);
    }

    public function testShouldDoNothingOnRequestStartForNotMainRequest(): void
    {
        $event = $this->createMock(RequestEvent::class);
        $event->expects(self::once())
            ->method('isMainRequest')
            ->willReturn(false);
        $this->bufferedProducer->expects(self::never())
            ->method('isBufferingEnabled');
        $this->bufferedProducer->expects(self::never())
            ->method('enableBuffering');

        $this->requestWatcher->onRequestStart($event);
    }

    public function testShouldEnableBufferingOnRequestStartIfBufferingIsNotEnabledYet(): void
    {
        $event = $this->createMock(RequestEvent::class);
        $event->expects(self::once())
            ->method('isMainRequest')
            ->willReturn(true);
        $this->bufferedProducer->expects(self::once())
            ->method('isBufferingEnabled')
            ->willReturn(false);
        $this->bufferedProducer->expects(self::once())
            ->method('enableBuffering');

        $this->requestWatcher->onRequestStart($event);
    }

    public function testShouldNotEnableBufferingOnRequestStartIfBufferingIsAlreadyEnabled(): void
    {
        $event = $this->createMock(RequestEvent::class);
        $event->expects(self::once())
            ->method('isMainRequest')
            ->willReturn(true);
        $this->bufferedProducer->expects(self::once())
            ->method('isBufferingEnabled')
            ->willReturn(true);
        $this->bufferedProducer->expects(self::never())
            ->method('enableBuffering');

        $this->requestWatcher->onRequestStart($event);
    }

    public function testShouldFlushBufferOnRequestEndIfBufferingIsEnabledAndHasMessagesInBuffer(): void
    {
        $event = new TerminateEvent(
            $this->createMock(HttpKernelInterface::class),
            new Request(),
            new Response()
        );

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

    public function testShouldNotFlushBufferOnRequestEndIfBufferingIsEnabledButNoMessagesInBuffer(): void
    {
        $event = new TerminateEvent(
            $this->createMock(HttpKernelInterface::class),
            new Request(),
            new Response()
        );

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

    public function testShouldNotFlushBufferOnRequestEndIfBufferingIsNotEnabled(): void
    {
        $event = new TerminateEvent(
            $this->createMock(HttpKernelInterface::class),
            new Request(),
            new Response()
        );

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
