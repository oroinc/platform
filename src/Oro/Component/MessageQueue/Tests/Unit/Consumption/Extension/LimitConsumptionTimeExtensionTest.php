<?php
namespace Oro\Component\MessageQueue\Tests\Unit\Consumption\Extension;

use Oro\Component\MessageQueue\Consumption\Context;
use Oro\Component\MessageQueue\Consumption\Extension\LimitConsumptionTimeExtension;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageConsumerInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerInterface;

class LimitConsumptionTimeExtensionTest extends \PHPUnit_Framework_TestCase
{
    public function testCouldBeConstructedWithRequiredArguments()
    {
        new LimitConsumptionTimeExtension(new \DateTime('+1 day'));
    }

    public function testOnBeforeReceiveShouldInterruptExecutionIfConsumptionTimeExceeded()
    {
        $context = $this->createContext();

        // guard
        $this->assertFalse($context->isExecutionInterrupted());

        // test
        $extension = new LimitConsumptionTimeExtension(new \DateTime('+1 second'));

        sleep(2);
        $extension->onBeforeReceive($context);

        $this->assertTrue($context->isExecutionInterrupted());
    }

    public function testOnIdleShouldInterruptExecutionIfConsumptionTimeExceeded()
    {
        $context = $this->createContext();

        // guard
        $this->assertFalse($context->isExecutionInterrupted());

        // test
        $extension = new LimitConsumptionTimeExtension(new \DateTime('+1 second'));

        sleep(2);
        $extension->onIdle($context);

        $this->assertTrue($context->isExecutionInterrupted());
    }

    public function testOnPostReceivedShouldInterruptExecutionIfConsumptionTimeExceeded()
    {
        $context = $this->createContext();

        // guard
        $this->assertFalse($context->isExecutionInterrupted());

        // test
        $extension = new LimitConsumptionTimeExtension(new \DateTime('+1 second'));

        sleep(2);
        $extension->onPostReceived($context);

        $this->assertTrue($context->isExecutionInterrupted());
    }

    public function testOnBeforeReceiveShouldNotInterruptExecutionIfConsumptionTimeIsNotExceeded()
    {
        $context = $this->createContext();

        // guard
        $this->assertFalse($context->isExecutionInterrupted());

        // test
        $extension = new LimitConsumptionTimeExtension(new \DateTime('+1 day'));

        $extension->onBeforeReceive($context);

        $this->assertFalse($context->isExecutionInterrupted());
    }

    public function testOnIdleShouldNotInterruptExecutionIfConsumptionTimeIsNotExceeded()
    {
        $context = $this->createContext();

        // guard
        $this->assertFalse($context->isExecutionInterrupted());

        // test
        $extension = new LimitConsumptionTimeExtension(new \DateTime('+1 day'));

        $extension->onIdle($context);

        $this->assertFalse($context->isExecutionInterrupted());
    }

    public function testOnPostReceivedShouldNotInterruptExecutionIfConsumptionTimeIsNotExceeded()
    {
        $context = $this->createContext();

        // guard
        $this->assertFalse($context->isExecutionInterrupted());

        // test
        $extension = new LimitConsumptionTimeExtension(new \DateTime('+1 day'));

        $extension->onPostReceived($context);

        $this->assertFalse($context->isExecutionInterrupted());
    }

    /**
     * @return Context
     */
    protected function createContext()
    {
        $context = new Context($this->getMock(SessionInterface::class));
        $context->setLogger($this->getMock(LoggerInterface::class));
        $context->setMessageConsumer($this->getMock(MessageConsumerInterface::class));
        $context->setMessageProcessor($this->getMock(MessageProcessorInterface::class));

        return $context;
    }
}
