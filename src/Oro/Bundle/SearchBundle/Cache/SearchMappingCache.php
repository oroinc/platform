<?php

namespace Oro\Bundle\SearchBundle\Cache;

use Symfony\Component\HttpKernel\CacheClearer\CacheClearerInterface;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

use Oro\Bundle\SearchBundle\Provider\SearchMappingProvider;

class SearchMappingCache implements CacheWarmerInterface, CacheClearerInterface
{
    /** @var SearchMappingProvider */
    protected $searchMappingProvider;

    /**
     * @param SearchMappingProvider $searchMappingProvider
     */
    public function __construct(SearchMappingProvider $searchMappingProvider)
    {
        $this->searchMappingProvider = $searchMappingProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function warmUp($cacheDir)
    {
        $this->searchMappingProvider->getMappingConfig();
    }

    /**
     * {@inheritdoc}
     */
    public function isOptional()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function clear($cacheDir)
    {
        $this->searchMappingProvider->clearCache();
    }
}
