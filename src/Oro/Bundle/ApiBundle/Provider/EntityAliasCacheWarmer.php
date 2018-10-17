<?php

namespace Oro\Bundle\ApiBundle\Provider;

use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

/**
 * Warms up entity aliases caches for all Data API types.
 */
class EntityAliasCacheWarmer implements CacheWarmerInterface
{
    /** @var EntityAliasResolverRegistry */
    private $entityAliasResolverRegistry;

    /**
     * @param EntityAliasResolverRegistry $entityAliasResolverRegistry
     */
    public function __construct(EntityAliasResolverRegistry $entityAliasResolverRegistry)
    {
        $this->entityAliasResolverRegistry = $entityAliasResolverRegistry;
    }

    /**
     * {@inheritdoc}
     */
    public function warmUp($cacheDir)
    {
        $this->warmUpCache();
    }

    /**
     * {@inheritdoc}
     */
    public function isOptional()
    {
        return false;
    }

    /**
     * Clears the cache.
     */
    public function clearCache()
    {
        $this->entityAliasResolverRegistry->clearCache();
    }

    /**
     * Warms up the cache.
     */
    public function warmUpCache()
    {
        $this->entityAliasResolverRegistry->warmUpCache();
    }
}
