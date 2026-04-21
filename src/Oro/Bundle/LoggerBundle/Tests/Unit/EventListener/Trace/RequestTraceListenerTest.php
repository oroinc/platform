<?php

namespace Oro\Bundle\LoggerBundle\Tests\Unit\EventListener\Trace;

use Oro\Bundle\LoggerBundle\EventListener\Trace\RequestTraceListener;
use Oro\Bundle\LoggerBundle\Trace\TraceManager;
use Oro\Bundle\LoggerBundle\Trace\TraceManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;

class RequestTraceListenerTest extends TestCase
{
    private const HEADER = 'X-Request-ID';
    private const TRACE_VALIDATION_REGEX = '/^[a-f0-9]{32}$/';

    private TraceManagerInterface $traceManager;
    private RequestTraceListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->traceManager = new TraceManager($dispatcher);

        $this->listener = new RequestTraceListener($this->traceManager, self::HEADER);
    }

    public function testOnRequestSetTraceFromHeader(): void
    {
        $expectedTrace = $this->traceManager->generate();
        $request = new Request();
        $request->headers->set(self::HEADER, $expectedTrace);

        $event = $this->createMock(RequestEvent::class);
        $event->method('getRequest')
            ->willReturn($request);

        $this->assertNull($this->traceManager->get());

        $this->listener->onRequest($event);

        $actualTrace = $this->traceManager->get();
        $this->assertEquals($expectedTrace, $actualTrace);
    }

    public function testOnRequestGenerateTraceIfHeaderMissing(): void
    {
        $request = new Request();

        $event = $this->createMock(RequestEvent::class);
        $event->method('getRequest')
            ->willReturn($request);

        $this->assertNull($this->traceManager->get());

        $this->listener->onRequest($event);

        $actualTrace = $this->traceManager->get();
        self::assertMatchesRegularExpression(self::TRACE_VALIDATION_REGEX, $actualTrace);
    }

    public function testOnRequestTraceAlreadyStored(): void
    {
        $event = $this->createMock(RequestEvent::class);
        $event->expects(self::never())
            ->method('getRequest');

        $expectedTrace = '77777777777777777777777777777777';
        $this->traceManager->set($expectedTrace);

        $this->listener->onRequest($event);

        $actualTrace = $this->traceManager->get();
        $this->assertEquals($expectedTrace, $actualTrace);
    }
}
