<?php
namespace Oro\Component\MessageQueue\Tests\Unit\Router;

use Oro\Component\MessageQueue\Consumption\MessageProcessor;
use Oro\Component\MessageQueue\Router\Recipient;
use Oro\Component\MessageQueue\Router\Router;
use Oro\Component\MessageQueue\Router\RouterMessageProcessor;
use Oro\Component\MessageQueue\Transport\MessageProducer;
use Oro\Component\MessageQueue\Transport\Null\NullMessage;
use Oro\Component\MessageQueue\Transport\Null\NullQueue;
use Oro\Component\MessageQueue\Transport\Session;
use Oro\Component\Testing\ClassExtensionTrait;

class RouterMessageProcessorTest extends \PHPUnit_Framework_TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementMessageProcessorInterface()
    {
        $this->assertClassImplements(MessageProcessor::class, RouterMessageProcessor::class);
    }

    public function testCouldBeConstructedWithRouterAsFirstArgument()
    {
        new RouterMessageProcessor($this->createRouterMock());
    }

    public function testShouldProduceRecipientsMessagesAndAckOriginalMessage()
    {
        $fooRecipient = new Recipient(new NullQueue('aName'), new NullMessage());
        $barRecipient = new Recipient(new NullQueue('aName'), new NullMessage());

        $originalMessage = new NullMessage();

        $routerMock = $this->createRouterMock();
        $routerMock
            ->expects($this->once())
            ->method('route')
            ->with($this->identicalTo($originalMessage))
            ->willReturn([$fooRecipient, $barRecipient])
        ;

        $producerMock = $this->createProducerMock();
        $producerMock
            ->expects($this->at(0))
            ->method('send')
            ->with($this->identicalTo($fooRecipient->getDestination()), $this->identicalTo($fooRecipient->getMessage()))
        ;
        $producerMock
            ->expects($this->at(1))
            ->method('send')
            ->with($this->identicalTo($barRecipient->getDestination()), $this->identicalTo($barRecipient->getMessage()))
        ;

        $sessionMock = $this->createSessionMock();
        $sessionMock
            ->expects($this->once())
            ->method('createProducer')
            ->willReturn($producerMock)
        ;

        $processor = new RouterMessageProcessor($routerMock);

        $status = $processor->process($originalMessage, $sessionMock);

        $this->assertEquals(MessageProcessor::ACK, $status);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|MessageProducer
     */
    protected function createProducerMock()
    {
        return $this->getMock(MessageProducer::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|Session
     */
    protected function createSessionMock()
    {
        return $this->getMock(Session::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|Router
     */
    protected function createRouterMock()
    {
        return $this->getMock(Router::class);
    }
}
