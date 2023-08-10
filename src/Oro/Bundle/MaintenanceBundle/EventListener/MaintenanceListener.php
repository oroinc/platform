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
 *
 * @author  Gilles Gauthier <g.gauthier@lexik.fr>
 */
class MaintenanceListener
{
    protected DriverFactory $driverFactory;

    protected RouterListener $routerListener;

    protected MaintenanceRestrictionsChecker $restrictionsChecker;

    protected ?int $httpCode;

    protected ?string $httpStatus;

    protected ?string $httpExceptionMessage;

    protected bool $handleResponse = false;

    protected bool $debug;

    /**
     * Constructor Listener
     *
     * Accepts a driver factory, and several arguments to be compared against the
     * incoming request.
     * When the maintenance mode is enabled, the request will be allowed to bypass
     * it if at least one of the provided arguments is not empty and matches the
     * incoming request.
     *
     * @param DriverFactory $driverFactory The driver factory
     * @param RouterListener $routerListener
     * @param MaintenanceRestrictionsChecker $restrictionsChecker
     * @param int|null $httpCode http status code for response
     * @param string|null $httpStatus http status message for response
     * @param string|null $httpExceptionMessage http response page exception message
     * @param bool $debug
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        DriverFactory $driverFactory,
        RouterListener $routerListener,
        MaintenanceRestrictionsChecker $restrictionsChecker,
        ?int $httpCode = null,
        ?string $httpStatus = null,
        ?string $httpExceptionMessage = null,
        bool $debug = false
    ) {
        $this->driverFactory = $driverFactory;
        $this->routerListener = $routerListener;
        $this->restrictionsChecker = $restrictionsChecker;
        $this->httpCode = $httpCode;
        $this->httpStatus = $httpStatus;
        $this->httpExceptionMessage = $httpExceptionMessage;
        $this->debug = $debug;
    }

    /**
     * @throws ServiceUnavailableHttpException
     *
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
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

    /**
     * Rewrites the http code of the response
     */
    public function onKernelResponse(ResponseEvent $event): void
    {
        if ($this->handleResponse && $this->httpCode !== null) {
            $response = $event->getResponse();
            $response->setStatusCode($this->httpCode, $this->httpStatus);
        }
    }
}
