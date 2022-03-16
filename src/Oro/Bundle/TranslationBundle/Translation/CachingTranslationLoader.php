<?php

namespace Oro\Bundle\TranslationBundle\Translation;

use Oro\Bundle\CacheBundle\Generator\UniversalCacheKeyGenerator;
use Symfony\Component\Translation\Loader\LoaderInterface;
use Symfony\Component\Translation\MessageCatalogue;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * Adapter for caching translations
 */
class CachingTranslationLoader implements LoaderInterface
{
    private LoaderInterface $innerLoader;
    private ?CacheInterface $cache;

    public function __construct(LoaderInterface $innerLoader, ?CacheInterface $cache = null)
    {
        $this->innerLoader = $innerLoader;
        $this->cache       = $cache;
    }

    public function load(mixed $resource, string $locale, string $domain = 'messages'): MessageCatalogue
    {
        $resourceKey = $this->getResourceKey($resource);
        if (null === $resourceKey) {
            return $this->innerLoader->load($resource, $locale, $domain);
        }
        if ($this->cache) {
            $cacheKey = UniversalCacheKeyGenerator::normalizeCacheKey(
                sprintf('%s_%s_%s', $locale, $domain, $resourceKey)
            );
            return $this->cache->get($cacheKey, function () use ($resource, $locale, $domain) {
                return $this->innerLoader->load($resource, $locale, $domain);
            });
        }
        return $this->innerLoader->load($resource, $locale, $domain);
    }

    /**
     * @param mixed $resource
     *
     * @return string|null
     */
    protected function getResourceKey(mixed $resource): ?string
    {
        if (is_object($resource) && method_exists($resource, '__toString')) {
            $resource = (string)$resource;
        }

        return is_string($resource) && !empty($resource)
            ? $resource
            : null;
    }
}
