<?php

namespace Oro\Bundle\ApiBundle\Provider;

use Nelmio\ApiDocBundle\Extractor\ApiDocExtractor;
use Oro\Bundle\ApiBundle\ApiDoc\Extractor\CachingApiDocExtractor;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Util\RequestExpressionMatcher;
use Symfony\Contracts\Service\ResetInterface;

/**
 * The class that can be used to manage API caches.
 */
class CacheManager
{
    /** @var array [config key => [request type aspect, ...], ...] */
    private array $configKeys;
    /** @var array [view => [request type aspect, ...], ...] */
    private array $apiDocViews;
    private RequestExpressionMatcher $matcher;
    private ConfigCacheFactory $configCacheFactory;
    private ConfigCacheWarmer $configCacheWarmer;
    private EntityAliasResolverRegistry $entityAliasResolverRegistry;
    private ResourcesCacheWarmer $resourcesCacheWarmer;
    private ApiDocExtractor $apiDocExtractor;
    private ConfigProvider $configProvider;
    /** @var ResetInterface[] */
    private array $resettableServices = [];

    public function __construct(
        array $configKeys,
        array $apiDocViews,
        RequestExpressionMatcher $matcher,
        ConfigCacheFactory $configCacheFactory,
        ConfigCacheWarmer $configCacheWarmer,
        EntityAliasResolverRegistry $entityAliasResolverRegistry,
        ResourcesCacheWarmer $resourcesCacheWarmer,
        ApiDocExtractor $apiDocExtractor,
        ConfigProvider $configProvider
    ) {
        $this->configKeys = $configKeys;
        $this->apiDocViews = $apiDocViews;
        $this->matcher = $matcher;
        $this->configCacheFactory = $configCacheFactory;
        $this->configCacheWarmer = $configCacheWarmer;
        $this->entityAliasResolverRegistry = $entityAliasResolverRegistry;
        $this->resourcesCacheWarmer = $resourcesCacheWarmer;
        $this->apiDocExtractor = $apiDocExtractor;
        $this->configProvider = $configProvider;
    }

    /**
     * Clears all API caches except API documentation cache.
     * To clear API documentation cache the clearApiDocCache() method should be used.
     */
    public function clearCaches(): void
    {
        $this->configCacheWarmer->warmUp();
        $this->entityAliasResolverRegistry->clearCache();
        $this->resourcesCacheWarmer->clearCache();
    }

    /**
     * Warms up all API caches except API documentation cache.
     * To warm up API documentation cache the warmUpApiDocCache() method should be used.
     */
    public function warmUpCaches(): void
    {
        $this->configCacheWarmer->warmUp();
        $this->entityAliasResolverRegistry->warmUpCache();
        $this->resourcesCacheWarmer->warmUpCache();
    }

    /**
     * Warms up all dirty API caches and clears all affected API documentation caches.
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function warmUpDirtyCaches(): void
    {
        $dirtyRequestTypeExpressions = [];
        foreach ($this->configKeys as $configKey => $aspects) {
            if (!$this->configCacheFactory->getCache($configKey)->isFresh()) {
                $this->configCacheWarmer->warmUp($configKey);
                $dirtyRequestTypeExpressions[] = implode('&', $aspects);
            }
        }
        if (!empty($dirtyRequestTypeExpressions)) {
            $this->entityAliasResolverRegistry->warmUpCache();
            $this->resourcesCacheWarmer->warmUpCache();
            if ($this->isApiDocCacheEnabled()) {
                $toClearApiDocView = [];
                foreach ($dirtyRequestTypeExpressions as $dirtyExpr) {
                    foreach ($this->apiDocViews as $view => $aspects) {
                        if ($this->matchRequestType($dirtyExpr, $aspects)) {
                            $toClearApiDocView[] = $view;
                        }
                    }
                }
                if (!empty($toClearApiDocView)) {
                    foreach (array_unique($toClearApiDocView) as $view) {
                        $this->clearApiDocCache($view);
                    }
                }
            }
        }
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

    private function matchRequestType(string $expr, array $toMatchAspects): bool
    {
        return
            !$expr
            || empty($toMatchAspects)
            || $this->matcher->matchValue($expr, new RequestType($toMatchAspects));
    }
}
