<?php

namespace Oro\Bundle\MaintenanceBundle\Maintenance;

use Symfony\Component\HttpFoundation\IpUtils;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Checks if an access to the application is granted in the maintenance mode.
 */
class MaintenanceRestrictionsChecker
{
    public function __construct(
        private RequestStack $requestStack,
        private ?string $path = null,
        private ?string $host = null,
        private ?string $route = null,
        private ?array $ips = [],
        private ?array $query = [],
        private ?array $cookie = [],
        private ?array $attributes = [],
        private ?bool $debug = false
    ) {
    }

    public function isAllowedIp(): bool
    {
        if (!$this->ips) {
            return false;
        }

        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            return false;
        }

        $requestedIp = $request->getClientIp();
        foreach ($this->ips as $ip) {
            if (IpUtils::checkIp($requestedIp, $ip)) {
                return true;
            }
        }

        return false;
    }

    public function isAllowedRoute(): bool
    {
        if (!$this->route) {
            return false;
        }

        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            return false;
        }

        $route = $request->get('_route');
        if (!$route) {
            return false;
        }

        return (preg_match('{' . $this->route . '}', $route)) || ($this->debug && '_' === $route[0]);
    }

    public function isAllowedQuery(): bool
    {
        if (!$this->query) {
            return false;
        }

        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            return false;
        }

        foreach ($this->query as $key => $pattern) {
            if (!$pattern) {
                continue;
            }
            $val = $request->get($key);
            if (!$val) {
                continue;
            }
            if (preg_match('{' . $pattern . '}', $val)) {
                return true;
            }
        }

        return false;
    }

    public function isAllowedCookie(): bool
    {
        if (!$this->cookie) {
            return false;
        }

        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            return false;
        }

        foreach ($this->cookie as $key => $pattern) {
            if (!$pattern) {
                continue;
            }
            $val = $request->cookies->get($key);
            if (!$val) {
                continue;
            }
            if (preg_match('{' . $pattern . '}', $val)) {
                return true;
            }
        }

        return false;
    }

    public function isAllowedHost(): bool
    {
        if (!$this->host) {
            return false;
        }

        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            return false;
        }

        return preg_match('{' . $this->host . '}i', $request->getHost());
    }

    public function isAllowedAttributes(): bool
    {
        if (!$this->attributes) {
            return false;
        }

        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            return false;
        }

        foreach ($this->attributes as $key => $pattern) {
            if (!$pattern) {
                continue;
            }
            $val = $request->attributes->get($key);
            if (!$val) {
                continue;
            }
            if (preg_match('{' . $pattern . '}', $val)) {
                return true;
            }
        }

        return false;
    }

    public function isAllowedPath(): bool
    {
        if (!$this->path) {
            return false;
        }

        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            return false;
        }

        return preg_match('{' . $this->path . '}', rawurldecode($request->getPathInfo()));
    }

    public function isAllowed(): bool
    {
        if ($this->isAllowedQuery()) {
            return true;
        }

        if ($this->isAllowedCookie()) {
            return true;
        }

        if ($this->isAllowedAttributes()) {
            return true;
        }

        if ($this->isAllowedPath()) {
            return true;
        }

        if ($this->isAllowedHost()) {
            return true;
        }

        if ($this->isAllowedIp()) {
            return true;
        }

        if ($this->isAllowedRoute()) {
            return true;
        }

        return false;
    }
}
