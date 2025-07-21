<?php

namespace Oro\Bundle\UIBundle\Tools;

use GuzzleHttp\Psr7\Uri;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\UrlHelper as SymfonyUrlHelper;
use Symfony\Component\Routing\RequestContext;

/**
 * Decorates {@see SymfonyUrlHelper} to add getAbsolutePath() method.
 */
class UrlHelper
{
    private SymfonyUrlHelper $symfonyUrlHelper;

    private RequestStack $requestStack;

    private ?RequestContext $requestContext;

    public function __construct(
        SymfonyUrlHelper $symfonyUrlHelper,
        RequestStack $requestStack,
        ?RequestContext $requestContext = null
    ) {
        $this->symfonyUrlHelper = $symfonyUrlHelper;
        $this->requestStack = $requestStack;
        $this->requestContext = $requestContext;
    }

    public function getAbsoluteUrl(string $path): string
    {
        return $this->symfonyUrlHelper->getAbsoluteUrl($path);
    }

    public function getRelativePath(string $path): string
    {
        return $this->symfonyUrlHelper->getRelativePath($path);
    }

    /**
     * Adds base url to the given path if the path is not an absolute URL or does not starts with base url.
     */
    public function getAbsolutePath(string $path): string
    {
        if (str_contains($path, '://') || '//' === substr($path, 0, 2)) {
            return $path;
        }

        $baseUrl = $this->getBaseUrl();

        if (!str_starts_with($path, $baseUrl)) {
            return rtrim($baseUrl, '/') . '/' . ltrim($path, '/');
        }

        return $path;
    }

    private function getBaseUrl(): string
    {
        return $this->requestContext?->getBaseUrl() ?: $this->requestStack->getMainRequest()?->getBaseUrl() ?: '';
    }

    /**
     * Checks if the given URL is a local URL.
     *
     * A URL is considered local if its host matches one of the predefined local hostnames
     * such as 'localhost', '127.0.0.1', or other common local addresses.
     *
     * @param string|null $url The URL to check. If null, the current request's URL is used.
     *
     * @return bool True if the URL is local, false otherwise.
     */
    public function isLocalUrl(?string $url = null): bool
    {
        $url ??= (string) $this->requestStack->getMainRequest()?->getUri();

        $uri = new Uri($url);
        $host = $uri->getHost();
        $localHosts = [
            '127.0.0.1',               // IPv4 localhost
            'localhost',               // Standard localhost hostname
            '0.0.0.0',                 // Non-routable meta-address (commonly used for binding to all interfaces)
            'localhost.localdomain',   // Commonly used in some configurations
            'localhost6',              // IPv6-specific localhost hostname
            'localhost6.localdomain6', // IPv6-specific localhost with local domain
        ];

        return in_array($host, $localHosts, true);
    }
}
