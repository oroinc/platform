<?php

namespace Oro\Bundle\PlatformBundle\EventListener;

use Lexik\Bundle\MaintenanceBundle\Drivers\DriverFactory;
use Lexik\Bundle\MaintenanceBundle\Listener\MaintenanceListener;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\EventListener\RouterListener;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Decorates listener which must decide if user can access to the site. Maintenance listener must be executed right
 * after RouterListener when maintenance is on to prevent context processing errors.
 */
class MaintenanceListenerDecorator extends MaintenanceListener
{
    /** @var MaintenanceListener */
    private $innerListener;

    /** @var RouterListener */
    private $routerListener;

    public function __construct(
        MaintenanceListener $innerListener,
        DriverFactory $driverFactory,
        RouterListener $routerListener
    ) {
        $this->innerListener = $innerListener;
        $this->driverFactory = $driverFactory;
        $this->routerListener = $routerListener;
    }

    public function onKernelRequest(GetResponseEvent $event): void
    {
        $driver = $this->driverFactory->getDriver();
        if (HttpKernelInterface::MASTER_REQUEST === $event->getRequestType() && $driver->decide()) {
            $this->routerListener->onKernelRequest($event);
        }

        $this->innerListener->onKernelRequest($event);
    }

    public function onKernelResponse(FilterResponseEvent $event): void
    {
        $this->innerListener->onKernelResponse($event);
    }
}
