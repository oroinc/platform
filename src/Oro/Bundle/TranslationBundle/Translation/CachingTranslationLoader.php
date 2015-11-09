<?php

namespace Oro\Bundle\TranslationBundle\Translation;

use Doctrine\Common\Cache\Cache;

use Symfony\Component\Translation\Loader\LoaderInterface;

class CachingTranslationLoader implements LoaderInterface
{
    /** @var LoaderInterface */
    private $innerLoader;

    /** @var Cache */
    private $cache = [];

    /**
     * @param LoaderInterface $innerLoader
     * @param Cache           $cache
     */
    public function __construct(LoaderInterface $innerLoader, Cache $cache)
    {
        $this->innerLoader = $innerLoader;
        $this->cache       = $cache;
    }

    /**
     * {@inheritdoc}
     */
    public function load($resource, $locale, $domain = 'messages')
    {
        $resourceKey = $this->getResourceKey($resource);
        if (null === $resourceKey) {
            return $this->innerLoader->load($resource, $locale, $domain);
        }

        $cacheKey = sprintf('%s_%s_%s', $locale, $domain, $resourceKey);

        $catalogue = $this->cache->fetch($cacheKey);
        if (false !== $catalogue) {
            return $catalogue;
        }

        $catalogue = $this->innerLoader->load($resource, $locale, $domain);
        $this->cache->save($cacheKey, $catalogue);

        return $catalogue;
    }

    /**
     * @param mixed $resource
     *
     * @return string|null
     */
    protected function getResourceKey($resource)
    {
        if (is_object($resource) && method_exists($resource, '__toString')) {
            $resource = (string)$resource;
        }

        return is_string($resource) && !empty($resource)
            ? $resource
            : null;
    }
}
