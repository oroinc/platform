<?php

namespace Oro\Bundle\LayoutBundle\Provider\Image;

use Oro\Bundle\CacheBundle\Generator\UniversalCacheKeyGenerator;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * Caching decorator for image placeholder provider.
 */
class CacheImagePlaceholderProvider implements ImagePlaceholderProviderInterface
{
    private ImagePlaceholderProviderInterface $imagePlaceholderProvider;
    private CacheInterface $cache;

    public function __construct(ImagePlaceholderProviderInterface $imagePlaceholderProvider, CacheInterface $cache)
    {
        $this->imagePlaceholderProvider = $imagePlaceholderProvider;
        $this->cache = $cache;
    }

    public function getPath(
        string $filter,
        string $format = '',
        int $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH
    ): ?string {
        $key = UniversalCacheKeyGenerator::normalizeCacheKey($filter .'|'. $format . '|' . $referenceType);
        return $this->cache->get($key, function () use ($filter, $format, $referenceType) {
            return $this->imagePlaceholderProvider->getPath($filter, $format, $referenceType);
        });
    }
}
