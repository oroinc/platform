<?php

namespace Oro\Bundle\PlatformBundle\EventListener;

use Lexik\Bundle\MaintenanceBundle\Exception\ServiceUnavailableException;
use Lexik\Bundle\MaintenanceBundle\Listener\MaintenanceListener as BaseMaintenanceListener;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Listener to decide if user can access to the site
 */
class MaintenanceListener extends BaseMaintenanceListener
{
    /**
     * {@inheritdoc}
     *
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function onKernelRequest(GetResponseEvent $event): void
    {
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

        if (count((array) $this->ips) !== 0 && $this->checkIps($request->getClientIp(), $this->ips)) {
            return;
        }

        $route = $request->get('_route');
        if ($route && (
            (null !== $this->route && preg_match('{' . $this->route . '}', $route)) ||
                (true === $this->debug && '_' === $route[0])
        )) {
            return;
        }

        // Get driver class defined in your configuration
        $driver = $this->driverFactory->getDriver();

        if ($driver->decide() && HttpKernelInterface::MASTER_REQUEST === $event->getRequestType()) {
            $this->handleResponse = true;
            throw new ServiceUnavailableException($this->http_exception_message);
        }
    }
}
