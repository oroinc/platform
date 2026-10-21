<?php

namespace Oro\Bundle\UIBundle\Tools;

use GuzzleHttp\Psr7\Uri;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\UrlHelper as SymfonyUrlHelper;
use Symfony\Component\Routing\RequestContext;

/**
 * Decorates {@see SymfonyUrlHelper} to add getAbsolutePath() method.
 */
class UrlHelper implements UrlHelperInterface
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

    #[\Override]
    public function getAbsoluteUrl(string $path): string
    {
        return $this->symfonyUrlHelper->getAbsoluteUrl($path);
    }

    #[\Override]
    public function getRelativePath(string $path): string
    {
        return $this->symfonyUrlHelper->getRelativePath($path);
    }

    #[\Override]
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

    #[\Override]
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
