<?php

namespace Oro\Bundle\TranslationBundle\Translation;

use Oro\Bundle\CacheBundle\Generator\UniversalCacheKeyGenerator;
use Oro\Bundle\CacheBundle\Provider\MemoryCache;
use Symfony\Component\Translation\Loader\LoaderInterface;
use Symfony\Component\Translation\MessageCatalogue;

/**
 * The translation resource loader wrapper that caches loaded resources into a memory cache.
 */
class CachingTranslationLoader implements LoaderInterface
{
    private LoaderInterface $innerLoader;
    private MemoryCache $cache;

    public function __construct(LoaderInterface $innerLoader, MemoryCache $cache)
    {
        $this->innerLoader = $innerLoader;
        $this->cache = $cache;
    }

    public function load(mixed $resource, string $locale, string $domain = 'messages'): MessageCatalogue
    {
        $resourceKey = $this->getResourceKey($resource);
        if (null === $resourceKey) {
            return $this->innerLoader->load($resource, $locale, $domain);
        }

        $cacheKey = $this->getCacheKey($locale, $domain, $resourceKey);
        $catalogue = $this->cache->get($cacheKey);
        if (null === $catalogue) {
            $catalogue = $this->innerLoader->load($resource, $locale, $domain);
            $this->cache->set($cacheKey, $catalogue);
        }

        return $catalogue;
    }

    private function getResourceKey(mixed $resource): ?string
    {
        if (\is_object($resource) && method_exists($resource, '__toString')) {
            $resource = (string)$resource;
        }

        return \is_string($resource) && !empty($resource)
            ? $resource
            : null;
    }

    private function getCacheKey(string $locale, string $domain, string $resourceKey): string
    {
        return UniversalCacheKeyGenerator::normalizeCacheKey(sprintf('%s_%s_%s', $locale, $domain, $resourceKey));
    }
}
