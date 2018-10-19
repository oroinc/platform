<?php

namespace Oro\Bundle\ApiBundle\Provider;

use Nelmio\ApiDocBundle\Extractor\ApiDocExtractor;
use Oro\Bundle\ApiBundle\ApiDoc\Extractor\CachingApiDocExtractor;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Util\RequestExpressionMatcher;

/**
 * The class that can be used to manage Data API caches.
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

    /** @var EntityAliasCacheWarmer */
    private $entityAliasCacheWarmer;

    /** @var ResourcesCacheWarmer */
    private $resourcesCacheWarmer;

    /** @var ApiDocExtractor */
    private $apiDocExtractor;

    /**
     * @param array                    $configKeys
     * @param array                    $apiDocViews
     * @param RequestExpressionMatcher $matcher
     * @param ConfigCacheFactory       $configCacheFactory
     * @param ConfigCacheWarmer        $configCacheWarmer
     * @param EntityAliasCacheWarmer   $entityAliasCacheWarmer
     * @param ResourcesCacheWarmer     $resourcesCacheWarmer
     * @param ApiDocExtractor          $apiDocExtractor
     */
    public function __construct(
        array $configKeys,
        array $apiDocViews,
        RequestExpressionMatcher $matcher,
        ConfigCacheFactory $configCacheFactory,
        ConfigCacheWarmer $configCacheWarmer,
        EntityAliasCacheWarmer $entityAliasCacheWarmer,
        ResourcesCacheWarmer $resourcesCacheWarmer,
        ApiDocExtractor $apiDocExtractor
    ) {
        $this->configKeys = $configKeys;
        $this->apiDocViews = $apiDocViews;
        $this->matcher = $matcher;
        $this->configCacheFactory = $configCacheFactory;
        $this->configCacheWarmer = $configCacheWarmer;
        $this->entityAliasCacheWarmer = $entityAliasCacheWarmer;
        $this->resourcesCacheWarmer = $resourcesCacheWarmer;
        $this->apiDocExtractor = $apiDocExtractor;
    }

    /**
     * Clears all Data API caches except API documentation cache.
     * To clear API documentation cache the clearApiDocCache() method should be used.
     */
    public function clearCaches()
    {
        $this->configCacheWarmer->warmUp();
        $this->entityAliasCacheWarmer->clearCache();
        $this->resourcesCacheWarmer->clearCache();
    }

    /**
     * Warms up all Data API caches except API documentation cache.
     * To warm up API documentation cache the warmUpApiDocCache() method should be used.
     */
    public function warmUpCaches()
    {
        $this->configCacheWarmer->warmUp();
        $this->entityAliasCacheWarmer->warmUpCache();
        $this->resourcesCacheWarmer->warmUpCache();
    }

    /**
     * Warms up all dirty Data API caches and clears all affected API documentation caches.
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
            $this->entityAliasCacheWarmer->warmUpCache();
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
            if ($view) {
                $this->apiDocExtractor->warmUp($view);
            } else {
                foreach ($this->apiDocViews as $currentView => $expr) {
                    $this->apiDocExtractor->warmUp($currentView);
                }
            }
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
