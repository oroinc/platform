<?php

namespace Oro\Bundle\LayoutBundle\Provider\Image;

use Doctrine\Common\Cache\Cache;

/**
 * Caching decorator for image placeholder provider.
 */
class CacheImagePlaceholderProvider implements ImagePlaceholderProviderInterface
{
    /** @var ImagePlaceholderProviderInterface */
    private $imagePlaceholderProvider;

    /** @var Cache */
    private $cache;

    public function __construct(ImagePlaceholderProviderInterface $imagePlaceholderProvider, Cache $cache)
    {
        $this->imagePlaceholderProvider = $imagePlaceholderProvider;
        $this->cache = $cache;
    }

    /**
     * {@inheritdoc}
     */
    public function getPath(string $filter): ?string
    {
        $path = $this->cache->fetch($filter);
        if (!$path) {
            $path = $this->imagePlaceholderProvider->getPath($filter);
            $this->cache->save($filter, $path);
        }

        return $path;
    }
}
