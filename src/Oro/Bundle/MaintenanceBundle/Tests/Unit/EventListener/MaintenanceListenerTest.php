<?php

namespace Oro\Bundle\MaintenanceBundle\Tests\Unit\EventListener;

use Oro\Bundle\MaintenanceBundle\Drivers\AbstractDriver;
use Oro\Bundle\MaintenanceBundle\Drivers\DriverFactory;
use Oro\Bundle\MaintenanceBundle\EventListener\MaintenanceListener;
use Oro\Bundle\MaintenanceBundle\Maintenance\MaintenanceRestrictionsChecker;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\EventListener\RouterListener;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class MaintenanceListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var AbstractDriver|\PHPUnit\Framework\MockObject\MockObject */
    private $driver;

    /** @var DriverFactory|\PHPUnit\Framework\MockObject\MockObject */
    private $driverFactory;

    /** @var RouterListener|\PHPUnit\Framework\MockObject\MockObject */
    private $routerListener;

    /** @var MaintenanceRestrictionsChecker|\PHPUnit\Framework\MockObject\MockObject */
    private $maintenanceRestrictionsChecker;

    /** @var MaintenanceListener */
    private $maintenanceListener;

    protected function setUp(): void
    {
        $this->driver = $this->createMock(AbstractDriver::class);

        $this->driverFactory = $this->createMock(DriverFactory::class);
        $this->driverFactory->expects(self::any())
            ->method('getDriver')
            ->willReturn($this->driver);

        $this->routerListener = $this->createMock(RouterListener::class);
        $this->maintenanceRestrictionsChecker = $this->createMock(MaintenanceRestrictionsChecker::class);

        $this->maintenanceListener = new MaintenanceListener(
            $this->driverFactory,
            $this->routerListener,
            $this->maintenanceRestrictionsChecker,
            503,
            null,
            ''
        );
    }

    public function testOnKernelRequestWhenMaintenanceAndMainRequest(): void
    {
        $this->expectException(ServiceUnavailableHttpException::class);

        $this->driver->expects(self::once())
            ->method('decide')
            ->willReturn(true);

        $event = $this->createMock(RequestEvent::class);
        $event->expects(self::atLeastOnce())
            ->method('isMainRequest')
            ->willReturn(true);

        $this->routerListener->expects(self::once())
            ->method('onKernelRequest')
            ->with($event);

        $this->maintenanceListener->onKernelRequest($event);
    }

    public function testOnKernelRequestWhenMaintenanceAndSubRequest(): void
    {
        $this->driver->expects(self::never())
            ->method('decide');

        $event = $this->createMock(RequestEvent::class);
        $event->expects(self::atLeastOnce())
            ->method('isMainRequest')
            ->willReturn(false);

        $this->routerListener->expects(self::never())
            ->method('onKernelRequest');

        $this->maintenanceListener->onKernelRequest($event);
    }

    public function testOnKernelRequestOnKernelRequestWhenNotMaintenance(): void
    {
        $this->driver->expects(self::once())
            ->method('decide')
            ->willReturn(false);

        $event = $this->createMock(RequestEvent::class);
        $event->expects(self::atLeastOnce())
            ->method('isMainRequest')
            ->willReturn(true);

        $this->routerListener->expects(self::never())
            ->method('onKernelRequest');

        $this->maintenanceListener->onKernelRequest($event);
    }
}
