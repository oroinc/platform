<?php
namespace Oro\Component\MessageQueue\Tests\Unit\Client\ConsumptionExtension;

use Oro\Component\MessageQueue\Consumption\Context;
use Oro\Component\MessageQueue\Consumption\ExtensionInterface;
use Oro\Component\MessageQueue\Transport\MessageConsumerInterface;
use Oro\Component\MessageQueue\Transport\Null\NullQueue;
use Oro\Component\MessageQueue\Client\ConsumptionExtension\CreateQueueExtension;
use Oro\Component\MessageQueue\Client\SessionInterface;
use Oro\Component\Testing\ClassExtensionTrait;

class CreateQueueExtensionTest extends \PHPUnit_Framework_TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementExtensionInterface()
    {
        $this->assertClassImplements(ExtensionInterface::class, CreateQueueExtension::class);
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
     * @return \PHPUnit_Framework_MockObject_MockObject|SessionInterface
     */
    protected function createSessionMock()
    {
        return $this->getMock(SessionInterface::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|Context
     */
    protected function createContextStub($queue = null)
    {
        $messageConsumerStub = $this->getMock(MessageConsumerInterface::class);
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

