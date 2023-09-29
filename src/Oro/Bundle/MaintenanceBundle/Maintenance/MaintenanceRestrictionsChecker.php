<?php

namespace Oro\Bundle\MaintenanceBundle\Maintenance;

use Symfony\Component\HttpFoundation\IpUtils;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class for checking is allow client access to app in maintenance mode
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
        $request = $this->requestStack->getCurrentRequest();
        $requestedIp = $request?->getClientIp();

        $valid = false;

        if (is_array($this->ips)) {
            $i = 0;
            while ($i < count($this->ips) && !$valid) {
                $valid = IpUtils::checkIp($requestedIp, $this->ips[$i]);
                $i++;
            }
        }

        return $valid;
    }

    public function isAllowedRoute(): bool
    {
        $request = $this->requestStack->getCurrentRequest();
        $route = $request?->get('_route');

        if (!$route) {
            return false;
        }

        if ($this->route &&
            (preg_match('{' . $this->route . '}', $route)) ||
            ($this->debug && '_' === $route[0])) {
            return true;
        }

        return false;
    }

    public function isAllowedQuery(): bool
    {
        $request = $this->requestStack->getCurrentRequest();

        if (is_array($this->query)) {
            foreach ($this->query as $key => $pattern) {
                if (!empty($pattern) && preg_match('{' . $pattern . '}', $request?->get($key))) {
                    return true;
                }
            }
        }

        return false;
    }

    public function isAllowedCookie(): bool
    {
        $request = $this->requestStack->getCurrentRequest();

        if (is_array($this->cookie)) {
            foreach ($this->cookie as $key => $pattern) {
                if (!empty($pattern) && preg_match('{' . $pattern . '}', $request?->cookies->get($key))) {
                    return true;
                }
            }
        }

        return false;
    }

    public function isAllowedHost(): bool
    {
        $request = $this->requestStack->getCurrentRequest();

        return !empty($this->host) && preg_match('{' . $this->host . '}i', $request?->getHost());
    }

    public function isAllowedAttributes(): bool
    {
        $request = $this->requestStack->getCurrentRequest();

        if (is_array($this->attributes)) {
            foreach ($this->attributes as $key => $pattern) {
                if (!empty($pattern) && preg_match('{' . $pattern . '}', $request?->attributes->get($key))) {
                    return true;
                }
            }
        }

        return false;
    }

    public function isAllowedPath(): bool
    {
        $request = $this->requestStack->getCurrentRequest();

        return !empty($this->path) &&
            preg_match('{' . $this->path . '}', rawurldecode($request?->getPathInfo()));
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
