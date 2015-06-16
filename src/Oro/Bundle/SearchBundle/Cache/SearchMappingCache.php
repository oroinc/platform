<?php

namespace Oro\Bundle\SearchBundle\Cache;

use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

use Oro\Bundle\SearchBundle\Provider\SearchMappingProvider;

class SearchMappingCache implements CacheWarmerInterface
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
     * {inheritdoc}
     */
    public function warmUp($cacheDir)
    {
        $this->searchMappingProvider->getMappingConfig();
    }

    /**
     * {inheritdoc}
     */
    public function isOptional()
    {
        return true;
    }
}
