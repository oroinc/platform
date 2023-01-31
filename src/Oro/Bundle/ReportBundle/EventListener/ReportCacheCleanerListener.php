<?php

namespace Oro\Bundle\ReportBundle\EventListener;

use Doctrine\Persistence\Event\LifecycleEventArgs;
use Oro\Bundle\ReportBundle\Entity\Report;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * Removes the report datagrid configuration from the cache when Report entity is updated.
 */
class ReportCacheCleanerListener
{
    private CacheInterface $cache;
    private string $prefixCacheKey;

    public function __construct(CacheInterface $cache, string $prefixCacheKey)
    {
        $this->cache = $cache;
        $this->prefixCacheKey = $prefixCacheKey;
    }

    public function postUpdate(Report $entity, LifecycleEventArgs $args): void
    {
        $cacheKey = $this->prefixCacheKey . '.' . $entity->getGridPrefix() . $entity->getId();
        $this->cache->delete($cacheKey);
    }
}
