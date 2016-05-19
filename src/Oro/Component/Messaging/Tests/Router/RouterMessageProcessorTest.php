<?php
namespace Oro\Component\Messaging\Tests\Router;

use Oro\Component\Messaging\Consumption\MessageProcessor;
use Oro\Component\Messaging\Router\Recipient;
use Oro\Component\Messaging\Router\Router;
use Oro\Component\Messaging\Router\RouterMessageProcessor;
use Oro\Component\Messaging\Transport\MessageProducer;
use Oro\Component\Messaging\Transport\Null\NullMessage;
use Oro\Component\Messaging\Transport\Null\NullQueue;
use Oro\Component\Messaging\Transport\Session;
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
