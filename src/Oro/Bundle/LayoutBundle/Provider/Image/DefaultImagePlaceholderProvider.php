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

    /**
     * {@inheritdoc}
     */
    public function getPath(string $filter, int $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH): ?string
    {
        return $this->imagineCacheManager->generateUrl($this->defaultPath, $filter, [], null, $referenceType);
    }
}
