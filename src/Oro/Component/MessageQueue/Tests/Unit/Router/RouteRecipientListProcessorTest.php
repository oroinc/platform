<?php
namespace Oro\Component\MessageQueue\Tests\Unit\Router;

use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Router\Recipient;
use Oro\Component\MessageQueue\Router\RecipientListRouterInterface;
use Oro\Component\MessageQueue\Router\RouteRecipientListProcessor;
use Oro\Component\MessageQueue\Transport\MessageProducerInterface;
use Oro\Component\MessageQueue\Transport\Null\NullMessage;
use Oro\Component\MessageQueue\Transport\Null\NullQueue;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\Testing\ClassExtensionTrait;

class RouteRecipientListProcessorTest extends \PHPUnit_Framework_TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementMessageProcessorInterface()
    {
        $this->assertClassImplements(MessageProcessorInterface::class, RouteRecipientListProcessor::class);
    }

    public function testCouldBeConstructedWithRouterAsFirstArgument()
    {
        new RouteRecipientListProcessor($this->createRecipientListRouterMock());
    }

    public function testShouldProduceRecipientsMessagesAndAckOriginalMessage()
    {
        $fooRecipient = new Recipient(new NullQueue('aName'), new NullMessage());
        $barRecipient = new Recipient(new NullQueue('aName'), new NullMessage());

        $originalMessage = new NullMessage();

        $routerMock = $this->createRecipientListRouterMock();
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

        $processor = new RouteRecipientListProcessor($routerMock);

        $status = $processor->process($originalMessage, $sessionMock);

        $this->assertEquals(MessageProcessorInterface::ACK, $status);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|MessageProducerInterface
     */
    protected function createProducerMock()
    {
        return $this->getMock(MessageProducerInterface::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|SessionInterface
     */
    protected function createSessionMock()
    {
        return $this->getMock(SessionInterface::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|RecipientListRouterInterface
     */
    protected function createRecipientListRouterMock()
    {
        return $this->getMock(RecipientListRouterInterface::class);
    }
}
