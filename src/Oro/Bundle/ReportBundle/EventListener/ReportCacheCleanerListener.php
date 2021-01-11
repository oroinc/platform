<?php

namespace Oro\Bundle\ReportBundle\EventListener;

use Doctrine\Common\Cache\Cache;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Oro\Bundle\ReportBundle\Entity\Report;

/**
 * Removes the report datagrid configuration from the cache when Report entity is updated.
 */
class ReportCacheCleanerListener
{
    /** @var Cache */
    private $cache;

    /** @var string */
    private $prefixCacheKey;

    /**
     * @param Cache  $cache
     * @param string $prefixCacheKey
     */
    public function __construct(Cache $cache, string $prefixCacheKey)
    {
        $this->cache = $cache;
        $this->prefixCacheKey = $prefixCacheKey;
    }

    /**
     * @param Report             $entity
     * @param LifecycleEventArgs $args
     */
    public function postUpdate(Report $entity, LifecycleEventArgs $args): void
    {
        $cacheKey = $this->prefixCacheKey . '.' . $entity->getGridPrefix() . $entity->getId();
        if ($this->cache->contains($cacheKey)) {
            $this->cache->delete($cacheKey);
        }
    }
}
