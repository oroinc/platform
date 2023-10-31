<?php

namespace Oro\Bundle\MaintenanceBundle\EventListener;

use Oro\Bundle\MaintenanceBundle\Drivers\DriverFactory;
use Oro\Bundle\MaintenanceBundle\Maintenance\MaintenanceRestrictionsChecker;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\EventListener\RouterListener;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

/**
 * Listener to decide if user can access to the site.
 * Maintenance listener must be executed right after RouterListener
 * when maintenance is on to prevent context processing errors.
 */
class MaintenanceListener
{
    private DriverFactory $driverFactory;
    private RouterListener $routerListener;
    private MaintenanceRestrictionsChecker $restrictionsChecker;
    private ?int $httpCode;
    private ?string $httpStatus;
    private ?string $httpExceptionMessage;
    private bool $handleResponse = false;

    public function __construct(
        DriverFactory $driverFactory,
        RouterListener $routerListener,
        MaintenanceRestrictionsChecker $restrictionsChecker,
        ?int $httpCode = null,
        ?string $httpStatus = null,
        ?string $httpExceptionMessage = null
    ) {
        $this->driverFactory = $driverFactory;
        $this->routerListener = $routerListener;
        $this->restrictionsChecker = $restrictionsChecker;
        $this->httpCode = $httpCode;
        $this->httpStatus = $httpStatus;
        $this->httpExceptionMessage = $httpExceptionMessage;
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $isMaintenanceOn = false;
        $driver = $this->driverFactory->getDriver();
        if ($event->isMainRequest() && $driver->decide()) {
            $isMaintenanceOn = true;
            $this->routerListener->onKernelRequest($event);
        }

        if (!$event->isMainRequest()) {
            return;
        }

        if ($this->restrictionsChecker->isAllowed()) {
            return;
        }

        if ($isMaintenanceOn) {
            $this->handleResponse = true;
            $event->stopPropagation();

            throw new ServiceUnavailableHttpException(null, $this->httpExceptionMessage);
        }
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if ($this->handleResponse && $this->httpCode !== null) {
            $event->getResponse()->setStatusCode($this->httpCode, $this->httpStatus);
        }
    }
}
