<?php
namespace Oro\Bundle\MessageQueueBundle\Tests\Unit\Consumption\Extension;

use Psr\Log\LoggerInterface;

use Oro\Bundle\MessageQueueBundle\Consumption\Extension\MaintenanceExtension;
use Oro\Bundle\PlatformBundle\Maintenance\Mode;
use Oro\Component\MessageQueue\Consumption\Context;
use Oro\Component\MessageQueue\Consumption\ExtensionInterface;
use Oro\Component\MessageQueue\Transport\Null\NullMessage;
use Oro\Component\MessageQueue\Transport\SessionInterface;

class MaintenanceExtensionTest extends \PHPUnit_Framework_TestCase
{
    public function testCouldBeConstructedWithRequiredArguments()
    {
        $maintenanceExtension = new MaintenanceExtension($this->createMaintenanceModMock(), 1);
        $this->assertInstanceOf(
            ExtensionInterface::class,
            $maintenanceExtension,
            'Extension must implement ExtensionInterface'
        );
    }

    public function testShouldDoNothingIfMaintenanceModIsOff()
    {
        $message = new NullMessage();

        $context = new Context($this->createSessionMock());
        $context->setQueueName('queue');
        $context->setMessage($message);

        $maintenanceMod = $this->createMaintenanceModMock();
        $maintenanceMod
            ->expects($this->once())
            ->method('isOn')
            ->willReturn(false);

        $extension = new MaintenanceExtension($maintenanceMod, 1);
        $extension->onBeforeReceive($context);

        self::assertFalse($context->isExecutionInterrupted());
        self::assertNull($context->getStatus());
    }

    public function testShouldSleepAnInterruptedMaintenanceModIsOn()
    {
        $message = new NullMessage();
        $logger = $this->createLoggerMock();
        $logger
            ->expects($this->once())
            ->method('debug')
            ->with('[MaintenanceExtension] Maintenance mode has been activated.');

        $context = new Context($this->createSessionMock());
        $context->setQueueName('queue');
        $context->setMessage($message);
        $context->setLogger($logger);

        $maintenanceMod = $this->createMaintenanceModMock();
        $maintenanceMod
            ->expects($this->at(0))
            ->method('isOn')
            ->willReturn(true);
        $maintenanceMod
            ->expects($this->at(1))
            ->method('isOn')
            ->willReturn(false);

        $extension = new MaintenanceExtension($maintenanceMod, 0.1);
        $extension->onBeforeReceive($context);

        self::assertNull($context->getStatus());
        self::assertTrue($context->isExecutionInterrupted());
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|SessionInterface
     */
    private function createSessionMock()
    {
        return $this->createMock(SessionInterface::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|Mode
     */
    private function createMaintenanceModMock()
    {
        return $this->createMock(Mode::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|LoggerInterface
     */
    private function createLoggerMock()
    {
        return $this->createMock(LoggerInterface::class);
    }
}
