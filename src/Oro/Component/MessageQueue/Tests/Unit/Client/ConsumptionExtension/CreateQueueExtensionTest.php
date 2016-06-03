<?php
namespace Oro\Component\MessageQueue\Tests\Unit\Client\ConsumptionExtension;

use Oro\Component\MessageQueue\Consumption\Context;
use Oro\Component\MessageQueue\Consumption\ExtensionInterface;
use Oro\Component\MessageQueue\Client\ConsumptionExtension\CreateQueueExtension;
use Oro\Component\MessageQueue\Client\DriverInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\Testing\ClassExtensionTrait;
use Psr\Log\LoggerInterface;

class CreateQueueExtensionTest extends \PHPUnit_Framework_TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementExtensionInterface()
    {
        $this->assertClassImplements(ExtensionInterface::class, CreateQueueExtension::class);
    }

    public function testCouldBeConstructedWithRequiredArguments()
    {
        new CreateQueueExtension($this->createDriverMock());
    }

    public function testShouldCreateQueueUsingQueueNameFromContext()
    {
        $loggerMock = $this->getMock(LoggerInterface::class);
        $loggerMock
            ->expects($this->once())
            ->method('debug')
            ->with('[CreateQueueExtension] Make sure the queue theQueueName exists on a broker side.')
        ;

        $context = new Context($this->createSessionMock());
        $context->setQueueName('theQueueName');
        $context->setLogger($loggerMock);

        $driverMock = $this->createDriverMock();
        $driverMock
            ->expects($this->once())
            ->method('createQueue')
            ->with('theQueueName')
        ;

        $extension = new CreateQueueExtension($driverMock);

        $extension->onBeforeReceive($context);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|SessionInterface
     */
    protected function createSessionMock()
    {
        return $this->getMock(SessionInterface::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|DriverInterface
     */
    protected function createDriverMock()
    {
        return $this->getMock(DriverInterface::class);
    }
}


