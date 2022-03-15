<?php

namespace Oro\Bundle\TranslationBundle\Translation;

use Psr\Cache\CacheItemPoolInterface;

/**
 * Stores the last update of database translations for locale in cache
 */
class DynamicTranslationMetadataCache
{
    private CacheItemPoolInterface $cacheImpl;

    public function __construct(CacheItemPoolInterface $cacheImpl)
    {
        $this->cacheImpl = $cacheImpl;
    }

    /**
     * Gets the timestamp of the last update of database translations for the given locale
     */
    public function getTimestamp(string $locale): int|bool
    {
        $cacheItem = $this->cacheImpl->getItem($locale);
        return $cacheItem->isHit() ? $cacheItem->get() : false;
    }

    /**
     * Renews the timestamp of the last update of database translations for the given locale
     */
    public function updateTimestamp(?string $locale = null): void
    {
        if ($locale) {
            $cacheItem = $this->cacheImpl->getItem($locale);
            $timestamp = (new \DateTime('now', new \DateTimeZone('UTC')))->getTimestamp();
            $this->cacheImpl->save($cacheItem->set($timestamp));
        } else {
            $this->cacheImpl->clear();
        }
    }
}
