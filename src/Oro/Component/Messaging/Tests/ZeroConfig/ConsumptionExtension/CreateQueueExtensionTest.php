<?php
namespace Oro\Component\Messaging\Tests\ZeroConfig\ConsumptionExtension;

use Oro\Component\Messaging\Consumption\Context;
use Oro\Component\Messaging\Consumption\Extension;
use Oro\Component\Messaging\Transport\MessageConsumer;
use Oro\Component\Messaging\Transport\Null\NullQueue;
use Oro\Component\Messaging\ZeroConfig\ConsumptionExtension\CreateQueueExtension;
use Oro\Component\Messaging\ZeroConfig\Session;
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

