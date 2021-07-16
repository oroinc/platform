<?php

namespace Oro\Bundle\MaintenanceBundle\Tests\Unit\Maintenance;

use Oro\Bundle\MaintenanceBundle\Drivers\AbstractDriver;
use Oro\Bundle\MaintenanceBundle\Drivers\DriverFactory;
use Oro\Bundle\MaintenanceBundle\Event\MaintenanceEvent;
use Oro\Bundle\MaintenanceBundle\Maintenance\Mode;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class ModeTest extends \PHPUnit\Framework\TestCase
{
    private Mode $mode;

    private AbstractDriver|\PHPUnit\Framework\MockObject\MockObject $driver;

    private EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject $dispatcher;

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

        $this->mode = new Mode($factory, $this->dispatcher);
    }

    public function testModeIsOn(): void
    {
        $this->driver->expects(self::once())
            ->method('decide')
            ->willReturn(true);

        self::assertTrue($this->mode->isOn());
    }

    public function testModeOn(): void
    {
        $this->dispatcher->expects(self::once())
            ->method('dispatch')
            ->with(new MaintenanceEvent(), MaintenanceEvent::MAINTENANCE_ON);

        self::assertTrue($this->mode->on());
    }

    public function testModeOff(): void
    {
        $this->dispatcher->expects(self::once())
            ->method('dispatch')
            ->with(new MaintenanceEvent(), MaintenanceEvent::MAINTENANCE_OFF);

        self::assertTrue($this->mode->off());
    }

    public function testActivate(): void
    {
        // can't check activation of maintenance, because it's turning off inside register_shutdown_function callback
        // it should be tested with Selenium tests
    }
}
