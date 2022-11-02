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
    private $configKeys;

    /** @var array [view => [request type aspect, ...], ...] */
    private $apiDocViews;

    /** @var RequestExpressionMatcher */
    private $matcher;

    /** @var ConfigCacheFactory */
    private $configCacheFactory;

    /** @var ConfigCacheWarmer */
    private $configCacheWarmer;

    /** @var EntityAliasResolverRegistry */
    private $entityAliasResolverRegistry;

    /** @var ResourcesCacheWarmer */
    private $resourcesCacheWarmer;

    /** @var ApiDocExtractor */
    private $apiDocExtractor;

    /** @var ConfigProvider */
    private $configProvider;

    /** @var ResetInterface[] */
    private $resettableServices = [];

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
    public function clearCaches()
    {
        $this->configCacheWarmer->warmUp();
        $this->entityAliasResolverRegistry->clearCache();
        $this->resourcesCacheWarmer->clearCache();
    }

    /**
     * Warms up all API caches except API documentation cache.
     * To warm up API documentation cache the warmUpApiDocCache() method should be used.
     */
    public function warmUpCaches()
    {
        $this->configCacheWarmer->warmUp();
        $this->entityAliasResolverRegistry->warmUpCache();
        $this->resourcesCacheWarmer->warmUpCache();
    }

    /**
     * Warms up all dirty API caches and clears all affected API documentation caches.
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function warmUpDirtyCaches()
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
     *
     * @return bool
     */
    public function isApiDocCacheEnabled()
    {
        return $this->apiDocExtractor instanceof CachingApiDocExtractor;
    }

    /**
     * Clears API documentation cache.
     *
     * @param string|null $view
     */
    public function clearApiDocCache($view = null)
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
     *
     * @param string|null $view
     */
    public function warmUpApiDocCache($view = null)
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
    public function addResettableService(ResetInterface $service)
    {
        $this->resettableServices[] = $service;
    }

    /**
     * Resets all registered resettable services to theirs initial state.
     */
    private function resetServices()
    {
        foreach ($this->resettableServices as $service) {
            $service->reset();
        }
    }

    /**
     * @param string $expr
     * @param array  $toMatchAspects
     *
     * @return bool
     */
    private function matchRequestType($expr, array $toMatchAspects)
    {
        return
            !$expr
            || empty($toMatchAspects)
            || $this->matcher->matchValue($expr, new RequestType($toMatchAspects));
    }
}
