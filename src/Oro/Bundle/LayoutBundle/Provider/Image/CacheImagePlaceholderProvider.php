<?php

namespace Oro\Bundle\LayoutBundle\Provider\Image;

use Doctrine\Common\Cache\Cache;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Caching decorator for image placeholder provider.
 */
class CacheImagePlaceholderProvider implements ImagePlaceholderProviderInterface
{
    private ImagePlaceholderProviderInterface $imagePlaceholderProvider;

    private Cache $cache;

    public function __construct(ImagePlaceholderProviderInterface $imagePlaceholderProvider, Cache $cache)
    {
        $this->imagePlaceholderProvider = $imagePlaceholderProvider;
        $this->cache = $cache;
    }

    /**
     * {@inheritdoc}
     */
    public function getPath(string $filter, int $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH): ?string
    {
        $key = $filter . '|' . $referenceType;

        $path = $this->cache->fetch($key);
        if (!$path) {
            $path = $this->imagePlaceholderProvider->getPath($filter, $referenceType);
            $this->cache->save($key, $path);
        }

        return $path;
    }
}
