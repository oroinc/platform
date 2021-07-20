<?php

namespace Oro\Bundle\MaintenanceBundle\EventListener;

use Oro\Bundle\MaintenanceBundle\Drivers\DriverFactory;
use Symfony\Component\HttpFoundation\IpUtils;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\EventListener\RouterListener;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;

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

    protected ?string $path;

    protected ?string $host;

    protected array $ips;

    protected array $query;

    protected array $cookie;

    protected ?string $route;

    protected array $attributes;

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
     * @param string|null $path A regex for the path
     * @param string|null $host A regex for the host
     * @param array $ips The list of IP addresses
     * @param array $query Query arguments
     * @param array $cookie Cookies
     * @param string|null $route Route name
     * @param array $attributes Attributes
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
        ?string $path = null,
        ?string $host = null,
        array $ips = [],
        array $query = [],
        array $cookie = [],
        ?string $route = null,
        array $attributes = [],
        ?int $httpCode = null,
        ?string $httpStatus = null,
        ?string $httpExceptionMessage = null,
        bool $debug = false
    ) {
        $this->driverFactory = $driverFactory;
        $this->routerListener = $routerListener;
        $this->path = $path;
        $this->host = $host;
        $this->ips = $ips;
        $this->query = $query;
        $this->cookie = $cookie;
        $this->route = $route;
        $this->attributes = $attributes;
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
        if (HttpKernelInterface::MASTER_REQUEST === $event->getRequestType() && $driver->decide()) {
            $isMaintenanceOn = true;
            $this->routerListener->onKernelRequest($event);
        }

        if (!$event->isMasterRequest()) {
            return;
        }

        $request = $event->getRequest();

        if (is_array($this->query)) {
            foreach ($this->query as $key => $pattern) {
                if (!empty($pattern) && preg_match('{'.$pattern.'}', $request->get($key))) {
                    return;
                }
            }
        }

        if (is_array($this->cookie)) {
            foreach ($this->cookie as $key => $pattern) {
                if (!empty($pattern) && preg_match('{'.$pattern.'}', $request->cookies->get($key))) {
                    return;
                }
            }
        }

        if (is_array($this->attributes)) {
            foreach ($this->attributes as $key => $pattern) {
                if (!empty($pattern) && preg_match('{'.$pattern.'}', $request->attributes->get($key))) {
                    return;
                }
            }
        }

        if (null !== $this->path &&
            !empty($this->path) &&
            preg_match('{' . $this->path . '}', rawurldecode($request->getPathInfo()))
        ) {
            return;
        }

        if (null !== $this->host && !empty($this->host) && preg_match('{' . $this->host . '}i', $request->getHost())) {
            return;
        }

        if (count($this->ips) !== 0 && $this->checkIps($request->getClientIp(), $this->ips)) {
            return;
        }

        $route = $request->get('_route');
        if ($route
            && (
                (null !== $this->route && preg_match('{' . $this->route . '}', $route))
                || (true === $this->debug && '_' === $route[0])
            )
        ) {
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

    /**
     * Checks if the requested IP is valid.
     */
    protected function checkIps(string $requestedIp, array $ips): bool
    {
        $valid = false;
        $i = 0;

        while ($i < count($ips) && !$valid) {
            $valid = IpUtils::checkIp($requestedIp, $ips[$i]);
            $i++;
        }

        return $valid;
    }
}
