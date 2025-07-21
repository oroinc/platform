<?php

namespace Oro\Bundle\MaintenanceBundle\Tests\Unit\Maintenance;

use Oro\Bundle\MaintenanceBundle\Drivers\AbstractDriver;
use Oro\Bundle\MaintenanceBundle\Drivers\DriverFactory;
use Oro\Bundle\MaintenanceBundle\Event\MaintenanceEvent;
use Oro\Bundle\MaintenanceBundle\Maintenance\MaintenanceModeState;
use Oro\Bundle\MaintenanceBundle\Maintenance\Mode;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class ModeTest extends TestCase
{
    private AbstractDriver&MockObject $driver;
    private EventDispatcherInterface&MockObject $dispatcher;
    private MaintenanceModeState $maintenanceModeState;
    private Mode $maintenanceMode;

    #[\Override]
    protected function setUp(): void
    {
        $this->driver = $this->createMock(AbstractDriver::class);
        $this->dispatcher = $this->createMock(EventDispatcherInterface::class);

        $factory = $this->createMock(DriverFactory::class);
        $factory->expects(self::any())
            ->method('getDriver')
            ->willReturn($this->driver);

        $this->driver->expects(self::any())
            ->method('lock')
            ->willReturn(true);
        $this->driver->expects(self::any())
            ->method('unlock')
            ->willReturn(true);

        $this->maintenanceMode = new Mode($factory, $this->dispatcher);
        $this->maintenanceModeState = new MaintenanceModeState($factory);
    }

    public function testModeIsOn(): void
    {
        $this->driver->expects(self::once())
            ->method('decide')
            ->willReturn(true);

        self::assertTrue($this->maintenanceModeState->isOn());
    }

    public function testModeOn(): void
    {
        $this->dispatcher->expects(self::once())
            ->method('dispatch')
            ->with(new MaintenanceEvent(), MaintenanceEvent::MAINTENANCE_ON);

        self::assertTrue($this->maintenanceMode->on());
    }

    public function testModeOff(): void
    {
        $this->dispatcher->expects(self::once())
            ->method('dispatch')
            ->with(new MaintenanceEvent(), MaintenanceEvent::MAINTENANCE_OFF);

        self::assertTrue($this->maintenanceMode->off());
    }

    public function testActivate(): void
    {
        // can't check activation of maintenance, because it's turning off inside register_shutdown_function callback
        // it should be tested with Selenium tests
    }
}
