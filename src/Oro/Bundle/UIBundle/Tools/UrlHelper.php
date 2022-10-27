<?php

namespace Oro\Bundle\UIBundle\Tools;

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

    private function getBaseUrl(): ?string
    {
        return $this->requestContext?->getBaseUrl() ?: $this->requestStack->getMainRequest()?->getBaseUrl();
    }
}
