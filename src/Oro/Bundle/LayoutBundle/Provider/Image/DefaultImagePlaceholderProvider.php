<?php

namespace Oro\Bundle\LayoutBundle\Provider\Image;

use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Provides the path to the default image placeholder.
 */
class DefaultImagePlaceholderProvider implements ImagePlaceholderProviderInterface
{
    private CacheManager $imagineCacheManager;

    private string $defaultPath;

    public function __construct(CacheManager $imagineCacheManager, string $defaultPath)
    {
        $this->imagineCacheManager = $imagineCacheManager;
        $this->defaultPath = $defaultPath;
    }

    public function getPath(
        string $filter,
        string $format = '',
        int $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH
    ): ?string {
        $path = $this->defaultPath;
        if ($format && pathinfo($path, PATHINFO_EXTENSION) !== $format) {
            $path .= '.' . $format;
        }

        return $this->imagineCacheManager->generateUrl($path, $filter, [], null, $referenceType);
    }
}
