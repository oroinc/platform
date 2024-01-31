<?php

namespace Oro\Bundle\ApiBundle\Provider;

use Nelmio\ApiDocBundle\Extractor\ApiDocExtractor;
use Oro\Bundle\ApiBundle\ApiDoc\Extractor\CachingApiDocExtractor;
use Symfony\Contracts\Service\ResetInterface;

/**
 * The class that can be used to manage API caches.
 */
class CacheManager
{
    /** @var array [view => [request type aspect, ...], ...] */
    private array $apiDocViews;
    private ConfigCacheWarmer $configCacheWarmer;
    private EntityAliasResolverRegistry $entityAliasResolverRegistry;
    private ResourcesCacheWarmer $resourcesCacheWarmer;
    private ApiDocExtractor $apiDocExtractor;
    private ConfigProvider $configProvider;
    /** @var ResetInterface[] */
    private array $resettableServices = [];

    public function __construct(
        array $apiDocViews,
        ConfigCacheWarmer $configCacheWarmer,
        EntityAliasResolverRegistry $entityAliasResolverRegistry,
        ResourcesCacheWarmer $resourcesCacheWarmer,
        ApiDocExtractor $apiDocExtractor,
        ConfigProvider $configProvider
    ) {
        $this->apiDocViews = $apiDocViews;
        $this->configCacheWarmer = $configCacheWarmer;
        $this->entityAliasResolverRegistry = $entityAliasResolverRegistry;
        $this->resourcesCacheWarmer = $resourcesCacheWarmer;
        $this->apiDocExtractor = $apiDocExtractor;
        $this->configProvider = $configProvider;
    }

    /**
     * Clears all API caches except API system cache and API documentation cache.
     * To rebuild API system cache the {@see warmUpConfigCache()} method should be used.
     * To clear API documentation cache the {@see clearApiDocCache()} method should be used.
     */
    public function clearCaches(): void
    {
        $this->entityAliasResolverRegistry->clearCache();
        $this->resourcesCacheWarmer->clearCache();
    }

    /**
     * Warms up all API caches except API system cache and API documentation cache.
     * To warm up API system cache the {@see warmUpConfigCache()} method should be used.
     * To warm up API documentation cache the {@see warmUpApiDocCache()} method should be used.
     */
    public function warmUpCaches(): void
    {
        $this->entityAliasResolverRegistry->warmUpCache();
        $this->resourcesCacheWarmer->warmUpCache();
    }

    /**
     * Warms up API system cache.
     */
    public function warmUpConfigCache(): void
    {
        $this->configCacheWarmer->warmUpCache();
    }

    /**
     * Checks if API documentation cache is enabled.
     */
    public function isApiDocCacheEnabled(): bool
    {
        return $this->apiDocExtractor instanceof CachingApiDocExtractor;
    }

    /**
     * Clears API documentation cache.
     */
    public function clearApiDocCache(?string $view = null): void
    {
        if ($this->apiDocExtractor instanceof CachingApiDocExtractor) {
            if ($view) {
                $this->apiDocExtractor->clear($view);
            } else {
                foreach ($this->apiDocViews as $currentView => $expr) {
                    $this->apiDocExtractor->clear($currentView);
                }
            }
        }
    }

    /**
     * Warms up API documentation cache.
     */
    public function warmUpApiDocCache(?string $view = null): void
    {
        if ($this->apiDocExtractor instanceof CachingApiDocExtractor) {
            $this->configProvider->disableFullConfigsCache();
            try {
                if ($view) {
                    $this->apiDocExtractor->warmUp($view);
                    $this->resetServices();
                } else {
                    foreach ($this->apiDocViews as $currentView => $expr) {
                        $this->apiDocExtractor->warmUp($currentView);
                        $this->resetServices();
                    }
                }
            } finally {
                $this->configProvider->enableFullConfigsCache();
            }
        }
    }

    /**
     * Registers a service that should be reset to its initial state
     * after API documentation cache for a specific view is warmed up.
     */
    public function addResettableService(ResetInterface $service): void
    {
        $this->resettableServices[] = $service;
    }

    /**
     * Resets all registered resettable services to theirs initial state.
     */
    private function resetServices(): void
    {
        foreach ($this->resettableServices as $service) {
            $service->reset();
        }
    }
}
