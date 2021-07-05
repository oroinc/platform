<?php

namespace Oro\Bundle\PlatformBundle\Tests\Unit\Maintenance;

use Lexik\Bundle\MaintenanceBundle\Drivers\DatabaseDriver;
use Lexik\Bundle\MaintenanceBundle\Drivers\DriverFactory;
use Oro\Bundle\PlatformBundle\Maintenance\MaintenanceEvent;
use Oro\Bundle\PlatformBundle\Maintenance\Mode;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ModeTest extends \PHPUnit\Framework\TestCase
{
    /** @var Mode */
    private $mode;

    /** @var DatabaseDriver */
    private $driver;

    /** @var EventDispatcherInterface */
    private $dispatcher;

    protected function setUp(): void
    {
        $this->driver = $this->createMock(DatabaseDriver::class);
        $this->dispatcher = $this->createMock(EventDispatcherInterface::class);

        $factory = $this->createMock(DriverFactory::class);
        $factory->expects($this->any())
            ->method('getDriver')
            ->willReturn($this->driver);

        $this->driver->expects($this->any())
            ->method('lock')
            ->willReturn(true);
        $this->driver->expects($this->any())
            ->method('unlock')
            ->willReturn(true);

        $this->mode = new Mode($factory, $this->dispatcher);
    }

    public function testModeIsOn()
    {
        $this->driver->expects($this->once())
            ->method('decide')
            ->willReturn(true);

        $this->assertTrue($this->mode->isOn());
    }

    public function testModeOn()
    {
        $this->dispatcher->expects($this->once())
            ->method('dispatch')
            ->with(new MaintenanceEvent(), MaintenanceEvent::MAINTENANCE_ON);

        $this->assertTrue($this->mode->on());
    }

    public function testModeOff()
    {
        $this->dispatcher->expects($this->once())
            ->method('dispatch')
            ->with(new MaintenanceEvent(), MaintenanceEvent::MAINTENANCE_OFF);

        $this->assertTrue($this->mode->off());
    }

    public function testActivate()
    {
        // can't check activation of maintenance, because it's turning off inside register_shutdown_function callback
        // it should be tested with Selenium tests
    }
}
