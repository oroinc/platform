<?php

namespace Oro\Bundle\LayoutBundle\Provider\Image;

use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Provides the path to the default image placeholder.
 */
class DefaultImagePlaceholderProvider implements ImagePlaceholderProviderInterface
{
    /** @var CacheManager */
    private $imagineCacheManager;

    /** @var string */
    private $defaultPath;

    public function __construct(CacheManager $imagineCacheManager, string $defaultPath)
    {
        $this->imagineCacheManager = $imagineCacheManager;
        $this->defaultPath = $defaultPath;
    }

    /**
     * {@inheritdoc}
     */
    public function getPath(string $filter): ?string
    {
        return $this->imagineCacheManager->generateUrl(
            $this->defaultPath,
            $filter,
            [],
            null,
            UrlGeneratorInterface::ABSOLUTE_PATH
        );
    }
}
