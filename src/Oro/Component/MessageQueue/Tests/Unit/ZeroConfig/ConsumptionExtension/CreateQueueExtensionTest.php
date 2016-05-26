<?php
namespace Oro\Component\MessageQueue\Tests\Unit\ZeroConfig\ConsumptionExtension;

use Oro\Component\MessageQueue\Consumption\Context;
use Oro\Component\MessageQueue\Consumption\Extension;
use Oro\Component\MessageQueue\Transport\MessageConsumer;
use Oro\Component\MessageQueue\Transport\Null\NullQueue;
use Oro\Component\MessageQueue\ZeroConfig\ConsumptionExtension\CreateQueueExtension;
use Oro\Component\MessageQueue\ZeroConfig\Session;
use Oro\Component\Testing\ClassExtensionTrait;

class CreateQueueExtensionTest extends \PHPUnit_Framework_TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementExtensionInterface()
    {
        $this->assertClassImplements(Extension::class, CreateQueueExtension::class);
    }

    public function testCouldBeConstructedWithRequiredArguments()
    {
        new CreateQueueExtension($this->createSessionMock());
    }

    public function testShouldCreateQueueUsingQueueNameFromContext()
    {
        $queue = new NullQueue('theQueueName');

        $context = $this->createContextStub($queue);

        $sessionMock = $this->createSessionMock();
        $sessionMock
            ->expects($this->once())
            ->method('createQueue')
            ->with('theQueueName')
        ;

        $extension = new CreateQueueExtension($sessionMock);

        $extension->onStart($context);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|Session
     */
    protected function createSessionMock()
    {
        return $this->getMock(Session::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|Context
     */
    protected function createContextStub($queue = null)
    {
        $messageConsumerStub = $this->getMock(MessageConsumer::class);
        $messageConsumerStub
            ->expects($this->any())
            ->method('getQueue')
            ->willReturn($queue)
        ;

        $contextMock = $this->getMock(Context::class, [], [], '', false);
        $contextMock
            ->expects($this->any())
            ->method('getMessageConsumer')
            ->willReturn($messageConsumerStub)
        ;

        return $contextMock;
    }
}

