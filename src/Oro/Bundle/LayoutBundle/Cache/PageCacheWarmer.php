<?php

namespace Oro\Bundle\LayoutBundle\Cache;

use Oro\Bundle\PlatformBundle\Provider\AbstractPageRequestProvider;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Warmup cache for provided pages.
 */
class PageCacheWarmer implements CacheWarmerInterface
{
    public function __construct(
        private iterable $pageRequestProviders,
        private HttpKernelInterface $httpKernel,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @inheritDoc
     */
    public function isOptional(): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function warmUp($cacheDir): void
    {
        foreach ($this->pageRequestProviders as $pageRequestProvider) {
            if (!$pageRequestProvider instanceof AbstractPageRequestProvider) {
                continue;
            }
            foreach ($pageRequestProvider->getRequests() as $request) {
                if (!$request instanceof Request) {
                    continue;
                }
                $this->warmPageCache($request);
            }
        }
    }

    private function warmPageCache(Request $request): void
    {
        try {
            $this->httpKernel->handle($request);
        } catch (\Throwable $exception) {
            $this->logger->warning(
                'Failed to warmup page cache: {message}',
                ['message' => $exception->getMessage(), 'exception' => $exception]
            );
        }
    }
}
