<?php

namespace Oro\Bundle\LoggerBundle\Tests\Unit\EventListener\Trace;

use Oro\Bundle\LoggerBundle\EventListener\Trace\ConsoleTraceListener;
use Oro\Bundle\LoggerBundle\Trace\TraceManager;
use Oro\Bundle\LoggerBundle\Trace\TraceManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ConsoleTraceListenerTest extends TestCase
{
    private const TRACE_VALIDATION_REGEX = '/^[a-f0-9]{32}$/';

    private TraceManagerInterface $traceManager;
    private ConsoleTraceListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->traceManager = new TraceManager($dispatcher);

        $this->listener = new ConsoleTraceListener($this->traceManager, null);
    }

    public function testOnConsoleCommand(): void
    {
        $this->assertNull($this->traceManager->get());

        $this->listener->onConsoleCommand();

        $actualTrace = $this->traceManager->get();
        self::assertMatchesRegularExpression(self::TRACE_VALIDATION_REGEX, $actualTrace);
    }

    public function testOnConsoleCommandTraceAlreadyStored(): void
    {
        $expectedTrace = '77777777777777777777777777777777';
        $this->traceManager->set($expectedTrace);

        $this->listener->onConsoleCommand();

        $actualTrace = $this->traceManager->get();
        $this->assertEquals($expectedTrace, $actualTrace);
    }
}
