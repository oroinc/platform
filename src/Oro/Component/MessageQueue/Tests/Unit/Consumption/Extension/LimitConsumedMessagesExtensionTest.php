<?php
namespace Oro\Component\MessageQueue\Tests\Unit\Consumption\Extension;


use Oro\Component\MessageQueue\Consumption\Context;
use Oro\Component\MessageQueue\Consumption\Extension\LimitConsumedMessagesExtension;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageConsumerInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerInterface;

class LimitConsumedMessagesExtensionTest extends \PHPUnit_Framework_TestCase
{
    public function testCouldBeConstructedWithRequiredArguments()
    {
        new LimitConsumedMessagesExtension(12345);
    }

    public function testShouldThrowExceptionIfMessageLimitIsNotInt()
    {
        $this->setExpectedException(
            \InvalidArgumentException::class,
            'Expected message limit is int but got: "double"'
        );

        new LimitConsumedMessagesExtension(0.0);
    }

    public function testShouldThrowExceptionIfMessageLimitIsLessThanZero()
    {
        $this->setExpectedException(\LogicException::class, 'Message limit must be more than zero but got: "-1"');

        new LimitConsumedMessagesExtension(-1);
    }

    public function testShouldThrowExceptionIfMessageLimitIsZero()
    {
        $this->setExpectedException(\LogicException::class, 'Message limit must be more than zero but got: "0"');

        new LimitConsumedMessagesExtension(0);
    }

    public function testShouldInterruptExecutionIfMessageLimitExceeded()
    {
        $context = $this->createContext();

        // guard
        $this->assertFalse($context->isExecutionInterrupted());

        // test
        $extension = new LimitConsumedMessagesExtension(2);

        // consume 1
        $extension->onPostReceived($context);
        $this->assertFalse($context->isExecutionInterrupted());

        // consume 2 and exit
        $extension->onPostReceived($context);
        $this->assertTrue($context->isExecutionInterrupted());
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
