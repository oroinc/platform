<?php

namespace Oro\Component\MessageQueue\Tests\Unit\Consumption\Extension;

use Oro\Component\MessageQueue\Consumption\Context;
use Oro\Component\MessageQueue\Consumption\Extension\LimitConsumerMemoryExtension;
use Oro\Component\MessageQueue\Transport\MessageConsumerInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class LimitConsumerMemoryExtensionTest extends TestCase
{
    public function testCouldBeConstructedWithRequiredArguments(): void
    {
        new LimitConsumerMemoryExtension(12345);
    }

    public function testShouldThrowExceptionIfMemoryLimitIsNotInt(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected memory limit is int but got: "double"');
        new LimitConsumerMemoryExtension(0.0);
    }

    public function testOnIdleShouldInterruptExecutionIfMemoryLimitReached(): void
    {
        $context = $this->createContext();
        $context->getLogger()->expects($this->once())
            ->method('debug')
            ->with($this->stringContains('Interrupt execution as memory limit reached.'));

        // guard
        $this->assertFalse($context->isExecutionInterrupted());

        // test
        $extension = new LimitConsumerMemoryExtension(1);
        $extension->onIdle($context);

        $this->assertTrue($context->isExecutionInterrupted());
    }

    public function testOnPostReceivedShouldInterruptExecutionIfMemoryLimitReached(): void
    {
        $context = $this->createContext();
        $context->getLogger()->expects($this->once())
            ->method('debug')
            ->with($this->stringContains('Interrupt execution as memory limit reached.'));

        // guard
        $this->assertFalse($context->isExecutionInterrupted());

        // test
        $extension = new LimitConsumerMemoryExtension(1);
        $extension->onPostReceived($context);

        $this->assertTrue($context->isExecutionInterrupted());
    }

    public function testOnBeforeReceivedShouldInterruptExecutionIfMemoryLimitReached(): void
    {
        $context = $this->createContext();
        $context->getLogger()->expects($this->once())
            ->method('debug')
            ->with($this->stringContains('Interrupt execution as memory limit reached.'));

        // guard
        $this->assertFalse($context->isExecutionInterrupted());

        // test
        $extension = new LimitConsumerMemoryExtension(1);
        $extension->onBeforeReceive($context);

        $this->assertTrue($context->isExecutionInterrupted());
    }

    public function testOnBeforeReceiveShouldNotInterruptExecutionIfMemoryLimitIsNotReached(): void
    {
        $context = $this->createContext();

        // guard
        $this->assertFalse($context->isExecutionInterrupted());

        // test
        $extension = new LimitConsumerMemoryExtension(PHP_INT_MAX);
        $extension->onBeforeReceive($context);

        $this->assertFalse($context->isExecutionInterrupted());
    }

    public function testOnIdleShouldNotInterruptExecutionIfMemoryLimitIsNotReached(): void
    {
        $context = $this->createContext();

        // guard
        $this->assertFalse($context->isExecutionInterrupted());

        // test
        $extension = new LimitConsumerMemoryExtension(PHP_INT_MAX);
        $extension->onIdle($context);

        $this->assertFalse($context->isExecutionInterrupted());
    }

    public function testOnPostReceivedShouldNotInterruptExecutionIfMemoryLimitIsNotReached(): void
    {
        $context = $this->createContext();

        // guard
        $this->assertFalse($context->isExecutionInterrupted());

        // test
        $extension = new LimitConsumerMemoryExtension(PHP_INT_MAX);
        $extension->onPostReceived($context);

        $this->assertFalse($context->isExecutionInterrupted());
    }

    /**
     * @return Context
     */
    protected function createContext()
    {
        $context = new Context($this->createMock(SessionInterface::class));
        $context->setLogger($this->createMock(LoggerInterface::class));
        $context->setMessageConsumer($this->createMock(MessageConsumerInterface::class));
        $context->setMessageProcessorName('sample_processor');

        return $context;
    }
}
