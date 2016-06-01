<?php
namespace Oro\Component\MessageQueue\Tests\Unit\Consumption\Extension;

use Oro\Component\MessageQueue\Consumption\Context;
use Oro\Component\MessageQueue\Consumption\Extension\LimitConsumerMemoryExtension;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageConsumerInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerInterface;

class LimitConsumerMemoryExtensionTest extends \PHPUnit_Framework_TestCase
{
    public function testCouldBeConstructedWithRequiredArguments()
    {
        new LimitConsumerMemoryExtension(12345);
    }

    public function testShouldThrowExceptionIfMemoryLimitIsNotInt()
    {
        $this->setExpectedException(\InvalidArgumentException::class, 'Expected memory limit is int but got: "double"');

        new LimitConsumerMemoryExtension(0.0);
    }

    public function testOnIdleShouldInterruptExecutionIfMemoryLimitReached()
    {
        $context = $this->createContext();
        $context->getLogger()
            ->expects($this->once())
            ->method('debug')
            ->with($this->stringContains('[LimitConsumerMemoryExtension] Interrupt execution as memory limit reached.'))
        ;

        // guard
        $this->assertFalse($context->isExecutionInterrupted());

        // test
        $extension = new LimitConsumerMemoryExtension(1);
        $extension->onIdle($context);

        $this->assertTrue($context->isExecutionInterrupted());
    }

    public function testOnPostReceivedShouldInterruptExecutionIfMemoryLimitReached()
    {
        $context = $this->createContext();
        $context->getLogger()
            ->expects($this->once())
            ->method('debug')
            ->with($this->stringContains('[LimitConsumerMemoryExtension] Interrupt execution as memory limit reached.'))
        ;

        // guard
        $this->assertFalse($context->isExecutionInterrupted());

        // test
        $extension = new LimitConsumerMemoryExtension(1);
        $extension->onPostReceived($context);

        $this->assertTrue($context->isExecutionInterrupted());
    }

    public function testOnBeforeReceivedShouldInterruptExecutionIfMemoryLimitReached()
    {
        $context = $this->createContext();
        $context->getLogger()
            ->expects($this->once())
            ->method('debug')
            ->with($this->stringContains('[LimitConsumerMemoryExtension] Interrupt execution as memory limit reached.'))
        ;

        // guard
        $this->assertFalse($context->isExecutionInterrupted());

        // test
        $extension = new LimitConsumerMemoryExtension(1);
        $extension->onBeforeReceive($context);

        $this->assertTrue($context->isExecutionInterrupted());
    }

    public function testOnBeforeReceiveShouldNotInterruptExecutionIfMemoryLimitIsNotReached()
    {
        $context = $this->createContext();

        // guard
        $this->assertFalse($context->isExecutionInterrupted());

        // test
        $extension = new LimitConsumerMemoryExtension(PHP_INT_MAX);
        $extension->onBeforeReceive($context);

        $this->assertFalse($context->isExecutionInterrupted());
    }

    public function testOnIdleShouldNotInterruptExecutionIfMemoryLimitIsNotReached()
    {
        $context = $this->createContext();

        // guard
        $this->assertFalse($context->isExecutionInterrupted());

        // test
        $extension = new LimitConsumerMemoryExtension(PHP_INT_MAX);
        $extension->onIdle($context);

        $this->assertFalse($context->isExecutionInterrupted());
    }

    public function testOnPostReceivedShouldNotInterruptExecutionIfMemoryLimitIsNotReached()
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
        return new Context(
            $this->getMock(SessionInterface::class),
            $this->getMock(MessageConsumerInterface::class),
            $this->getMock(MessageProcessorInterface::class),
            $this->getMock(LoggerInterface::class)
        );
    }
}
