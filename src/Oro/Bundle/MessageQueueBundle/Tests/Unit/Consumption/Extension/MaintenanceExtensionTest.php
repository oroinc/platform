<?php

namespace Oro\Bundle\MessageQueueBundle\Tests\Unit\Consumption\Extension;

use Oro\Bundle\MessageQueueBundle\Consumption\Extension\MaintenanceExtension;
use Oro\Bundle\PlatformBundle\Maintenance\Mode;
use Oro\Component\MessageQueue\Consumption\Context;
use Psr\Log\LoggerInterface;

class MaintenanceExtensionTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|Mode */
    private $maintenance;

    /** @var MaintenanceExtension */
    private $extension;

    protected function setUp()
    {
        $this->maintenance = $this->createMock(Mode::class);

        $this->extension = new MaintenanceExtension($this->maintenance, 1);
    }

    public function testShouldDoNothingIfMaintenanceModIsOff()
    {
        $context = $this->createMock(Context::class);

        $this->maintenance->expects($this->once())
            ->method('isOn')
            ->willReturn(false);
        $context->expects($this->never())
            ->method('setExecutionInterrupted');
        $context->expects($this->never())
            ->method('setInterruptedReason');

        $this->extension->onBeforeReceive($context);
    }

    public function testShouldSleepAnInterruptedMaintenanceModIsOn()
    {
        $context = $this->createMock(Context::class);
        $logger = $this->createMock(LoggerInterface::class);

        $context->expects($this->any())
            ->method('getLogger')
            ->willReturn($logger);

        $logger->expects($this->once())
            ->method('notice')
            ->with('The maintenance mode has been activated.');
        $logger->expects($this->once())
            ->method('info')
            ->with('Waiting for the maintenance mode deactivation.');

        $this->maintenance->expects($this->at(0))
            ->method('isOn')
            ->willReturn(true);
        $this->maintenance->expects($this->at(1))
            ->method('isOn')
            ->willReturn(false);
        $context->expects($this->once())
            ->method('setExecutionInterrupted')
            ->with(true);
        $context->expects($this->once())
            ->method('setInterruptedReason')
            ->with('The Maintenance mode has been deactivated.');

        $this->extension->onBeforeReceive($context);
    }
}
