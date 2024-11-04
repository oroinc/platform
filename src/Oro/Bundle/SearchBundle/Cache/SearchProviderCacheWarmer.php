<?php

namespace Oro\Bundle\SearchBundle\Cache;

use Oro\Bundle\SearchBundle\Provider\SearchMappingProvider;
use Symfony\Component\HttpKernel\CacheClearer\CacheClearerInterface;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

/**
 * Warms cache for search mapping provider
 */
class SearchProviderCacheWarmer implements CacheClearerInterface, CacheWarmerInterface
{
    private SearchMappingProvider $searchMappingProvider;

    public function __construct(SearchMappingProvider $searchMappingProvider)
    {
        $this->searchMappingProvider = $searchMappingProvider;
    }

    #[\Override]
    public function warmUp($cacheDir)
    {
        $this->searchMappingProvider->warmUpCache();
    }

    #[\Override]
    public function isOptional()
    {
        return true;
    }

    #[\Override]
    public function clear($cacheDir)
    {
        $this->searchMappingProvider->clearCache();
    }
}
