<?php

namespace Oro\Bundle\PlatformBundle\Tests\Unit\EventListener;

use Lexik\Bundle\MaintenanceBundle\Drivers\AbstractDriver;
use Lexik\Bundle\MaintenanceBundle\Drivers\DriverFactory;
use Lexik\Bundle\MaintenanceBundle\Listener\MaintenanceListener;
use Oro\Bundle\PlatformBundle\EventListener\MaintenanceListenerDecorator;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\EventListener\RouterListener;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class MaintenanceListenerDecoratorTest extends \PHPUnit\Framework\TestCase
{
    /** @var MaintenanceListener|\PHPUnit\Framework\MockObject\MockObject */
    private $innerListener;

    /** @var AbstractDriver|\PHPUnit\Framework\MockObject\MockObject */
    private $driver;

    /** @var RouterListener|\PHPUnit\Framework\MockObject\MockObject */
    private $routerListener;

    /** @var MaintenanceListenerDecorator */
    private $decorator;

    protected function setUp(): void
    {
        $this->innerListener = $this->createMock(MaintenanceListener::class);

        $this->driver = $this->createMock(AbstractDriver::class);

        $factory = $this->createMock(DriverFactory::class);
        $factory->expects($this->any())
            ->method('getDriver')
            ->willReturn($this->driver);

        $this->routerListener = $this->createMock(RouterListener::class);

        $this->decorator = new MaintenanceListenerDecorator($this->innerListener, $factory, $this->routerListener);
    }

    public function testOnKernelRequestWhenMaintenanceAndMasterRequest(): void
    {
        $this->driver->expects($this->once())
            ->method('decide')
            ->willReturn(true);

        $event = $this->createMock(GetResponseEvent::class);
        $event->expects($this->once())
            ->method('getRequestType')
            ->willReturn(HttpKernelInterface::MASTER_REQUEST);

        $this->routerListener->expects($this->once())
            ->method('onKernelRequest')
            ->with($event);

        $this->decorator->onKernelRequest($event);
    }

    public function testOnKernelRequestWhenMaintenanceAndSubRequest(): void
    {
        $this->driver->expects($this->never())
            ->method('decide');

        $event = $this->createMock(GetResponseEvent::class);
        $event->expects($this->once())
            ->method('getRequestType')
            ->willReturn(HttpKernelInterface::SUB_REQUEST);

        $this->routerListener->expects($this->never())
            ->method('onKernelRequest');

        $this->decorator->onKernelRequest($event);
    }

    public function testOnKernelRequestOnKernelRequestWhenNotMaintenance(): void
    {
        $this->driver->expects($this->once())
            ->method('decide')
            ->willReturn(false);

        $event = $this->createMock(GetResponseEvent::class);
        $event->expects($this->once())
            ->method('getRequestType')
            ->willReturn(HttpKernelInterface::MASTER_REQUEST);

        $this->routerListener->expects($this->never())
            ->method('onKernelRequest');

        $this->decorator->onKernelRequest($event);
    }

    public function testOnKernelResponse(): void
    {
        $event = $this->createMock(FilterResponseEvent::class);

        $this->innerListener->expects($this->once())
            ->method('onKernelResponse')
            ->with($event);

        $this->decorator->onKernelResponse($event);
    }
}
