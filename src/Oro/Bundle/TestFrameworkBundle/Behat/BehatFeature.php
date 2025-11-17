<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat;

use Psr\Cache\CacheItemPoolInterface;

/**
 * Manages active Behat feature tags using a cache.
 */
class BehatFeature
{
    private const CACHE_KEY = 'active_tags';

    public function __construct(
        private CacheItemPoolInterface $cache
    ) {
    }

    public function setActiveTags(array $tags): void
    {
        $item = $this->cache->getItem(self::CACHE_KEY);
        $item->set($tags);
        $this->cache->save($item);
    }

    public function clearActiveTags(): void
    {
        $this->cache->deleteItem(self::CACHE_KEY);
    }

    public function hasTag(string $tag): bool
    {
        $item = $this->cache->getItem(self::CACHE_KEY);
        if (!$item->isHit()) {
            return false;
        }

        $activeTags = $item->get();
        return in_array($tag, $activeTags, true);
    }
}
