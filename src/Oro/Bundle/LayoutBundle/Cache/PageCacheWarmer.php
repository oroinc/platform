<?php

namespace Oro\Bundle\LayoutBundle\Cache;

use Oro\Bundle\PlatformBundle\Provider\AbstractPageRequestProvider;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Contracts\Service\ResetInterface;

/**
 * Warmup cache for provided pages.
 */
class PageCacheWarmer implements CacheWarmerInterface
{
    public function __construct(
        private iterable $pageRequestProviders,
        private HttpKernelInterface $httpKernel,
        private LoggerInterface $logger,
        private ?ResetInterface $servicesResetter = null,
    ) {
    }

    #[\Override]
    public function isOptional(): bool
    {
        return true;
    }

    #[\Override]
    public function warmUp($cacheDir, ?string $buildDir = null): array
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
        return [];
    }

    private function warmPageCache(Request $request): void
    {
        $obLevel = ob_get_level();
        ob_start();
        try {
            $this->httpKernel->handle($request);
        } catch (\Throwable $exception) {
            $this->logger->warning(
                'Failed to warmup page cache: {message}',
                ['message' => $exception->getMessage(), 'exception' => $exception]
            );
        } finally {
            while (ob_get_level() > $obLevel) {
                ob_end_clean();
            }
            $this->resetServices();
        }
    }

    /**
     * All pages are handled in the same process. Without a reset, entities loaded by one page stay in the
     * Doctrine identity map and are given to the next page as proxies, so caches keyed by the entity class
     * name (e.g. the property accessor cache) are warmed for the proxy class instead of the entity class
     * used by real requests. Resetting the services the same way it is done between real requests
     * makes each page start from a clean state.
     */
    private function resetServices(): void
    {
        $this->servicesResetter?->reset();
    }
}
