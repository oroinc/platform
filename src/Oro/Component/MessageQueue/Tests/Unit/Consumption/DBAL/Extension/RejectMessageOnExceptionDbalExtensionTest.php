<?php
namespace Oro\Component\MessageQueue\Tests\Unit\Consumption\Dbal\Extension;

use Oro\Component\MessageQueue\Consumption\Context;
use Oro\Component\MessageQueue\Consumption\Dbal\Extension\RejectMessageOnExceptionDbalExtension;
use Oro\Component\MessageQueue\Transport\MessageConsumerInterface;
use Oro\Component\MessageQueue\Transport\Null\NullMessage;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerInterface;

class RejectMessageOnExceptionDbalExtensionTest extends \PHPUnit_Framework_TestCase
{
    public function testCouldBeCreatedWithRequiredArguments()
    {
        new RejectMessageOnExceptionDbalExtension();
    }

    public function testShouldDoNothingIfExceptionIsMissing()
    {
        $consumer = $this->createMessageConsumerMock();
        $consumer
            ->expects($this->never())
            ->method('reject')
        ;

        $context = new Context($this->createSessionMock());
        $context->setMessageConsumer($consumer);

        $extension = new RejectMessageOnExceptionDbalExtension();
        $extension->onInterrupted($context);
    }

    public function testShouldDoNothingIfMessageIsMissing()
    {
        $consumer = $this->createMessageConsumerMock();
        $consumer
            ->expects($this->never())
            ->method('reject')
        ;

        $context = new Context($this->createSessionMock());
        $context->setException(new \Exception());
        $context->setMessageConsumer($consumer);

        $extension = new RejectMessageOnExceptionDbalExtension();
        $extension->onInterrupted($context);
    }

    public function testShouldRejectMessage()
    {
        $message = new NullMessage();

        $logger = $this->createLoggerMock();
        $logger
            ->expects($this->once())
            ->method('debug')
            ->with('[RejectMessageOnExceptionDbalExtension] Execution was interrupted and message was rejected')
        ;

        $consumer = $this->createMessageConsumerMock();
        $consumer
            ->expects($this->once())
            ->method('reject')
            ->with($this->identicalTo($message), $this->isTrue())
        ;

        $context = new Context($this->createSessionMock());
        $context->setLogger($logger);
        $context->setException(new \Exception());
        $context->setMessage($message);
        $context->setMessageConsumer($consumer);

        $extension = new RejectMessageOnExceptionDbalExtension();
        $extension->onInterrupted($context);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function createMessageConsumerMock()
    {
        return $this->createMock(MessageConsumerInterface::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|SessionInterface
     */
    private function createSessionMock()
    {
        return $this->createMock(SessionInterface::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|LoggerInterface
     */
    private function createLoggerMock()
    {
        return $this->createMock(LoggerInterface::class);
    }
}
