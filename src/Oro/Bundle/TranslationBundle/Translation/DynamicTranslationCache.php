<?php

namespace Oro\Bundle\TranslationBundle\Translation;

use Oro\Bundle\TranslationBundle\Event\InvalidateDynamicTranslationCacheEvent;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * The cache for dynamic translations.
 */
class DynamicTranslationCache
{
    private const CACHE_KEY_PREFIX = 'dynamic_translations_';

    private CacheItemPoolInterface $cache;
    private EventDispatcherInterface $eventDispatcher;

    public function __construct(CacheItemPoolInterface $cache, EventDispatcherInterface $eventDispatcher)
    {
        $this->cache = $cache;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * Fetches translations from the cache for the given locales.
     *
     * For locales that do not have translations in the cache,
     * a callback is called that should return translations for these locales.
     *
     * @param string[] $locales
     * @param callable $callback function (array $notCachedLocales): array
     *
     * @return array [locale => [domain => [message id => message, ...], ...], ...]
     */
    public function get(array $locales, callable $callback): array
    {
        $translations = [];

        $items = $this->cache->getItems(self::getCacheKeys($locales));
        $notCachedItems = [];
        /** @var CacheItemInterface $item */
        foreach ($items as $key => $item) {
            $loc = substr($key, \strlen(self::CACHE_KEY_PREFIX));
            if ($item->isHit()) {
                $translations[$loc] = $item->get();
            } else {
                $notCachedItems[$loc] = $item;
            }
        }

        if ($notCachedItems) {
            $notCachedTranslations = $callback(array_keys($notCachedItems));
            foreach ($notCachedItems as $loc => $item) {
                $trans = $notCachedTranslations[$loc] ?? [];
                $translations[$loc] = $trans;
                $item->set($trans);
                $this->cache->saveDeferred($item);
            }
            $this->cache->commit();
        }

        return $translations;
    }

    /**
     * Removes translations from the cache for the given locales.
     */
    public function delete(array $locales): void
    {
        if ($locales) {
            $this->cache->deleteItems(self::getCacheKeys($locales));
            $this->eventDispatcher->dispatch(
                new InvalidateDynamicTranslationCacheEvent($locales),
                InvalidateDynamicTranslationCacheEvent::NAME
            );
        }
    }

    private static function getCacheKeys(array $locales): array
    {
        return array_map(static function (string $locale) {
            return self::CACHE_KEY_PREFIX . $locale;
        }, $locales);
    }
}
