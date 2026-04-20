<?php

namespace Oro\Bundle\LoggerBundle\Tests\Unit\Trace;

use Oro\Bundle\LoggerBundle\Trace\TraceManager;
use Oro\Bundle\LoggerBundle\Trace\TraceManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class TraceManagerTest extends TestCase
{
    private const TRACE_VALIDATION_REGEX = '/^[a-f0-9]{32}$/';

    private TraceManagerInterface $traceManager;

    #[\Override]
    protected function setUp(): void
    {
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->traceManager = new TraceManager($dispatcher);
    }

    public function testInitialState(): void
    {
        self::assertNull($this->traceManager->get());
    }

    public function testSetAndGet(): void
    {
        $firstTrace = '55555555555555555555555555555555';
        $secondTrace = '77777777777777777777777777777777';

        self::assertNull($this->traceManager->get());

        $this->traceManager->set($firstTrace);
        self::assertEquals($firstTrace, $this->traceManager->get());

        $this->traceManager->set($secondTrace);
        self::assertEquals($secondTrace, $this->traceManager->get());
    }

    public function testGetWithNullArg(): void
    {
        self::assertNull($this->traceManager->get());

        $this->traceManager->set();

        $actualTrace = $this->traceManager->get();
        self::assertMatchesRegularExpression(self::TRACE_VALIDATION_REGEX, $actualTrace);
    }

    public function testValidate(): void
    {
        $invalidTrace = 'InvalidTrace';
        $validTrace = $this->traceManager->generate();

        self::assertFalse($this->traceManager->validate($invalidTrace));
        self::assertTrue($this->traceManager->validate($validTrace));
    }

    public function testReset(): void
    {
        $this->traceManager->set();

        $generatedTrace = $this->traceManager->get();
        self::assertMatchesRegularExpression(self::TRACE_VALIDATION_REGEX, $generatedTrace);

        $this->traceManager->reset();
        self::assertNull($this->traceManager->get());
    }
}
