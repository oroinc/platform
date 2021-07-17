<?php

namespace Oro\Bundle\AttachmentBundle\Provider;

use Oro\Bundle\AttachmentBundle\Entity\File;

/**
 * The file path provider that assumes that an image path is the same as absolute URL path.
 */
class ResizedImagePathProvider implements ResizedImagePathProviderInterface
{
    /** @var FileUrlProviderInterface */
    private $fileUrlProvider;

    public function __construct(FileUrlProviderInterface $fileUrlProvider)
    {
        $this->fileUrlProvider = $fileUrlProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function getPathForResizedImage(File $entity, int $width, int $height): string
    {
        return $this->normalizePath(
            $this->fileUrlProvider->getResizedImageUrl($entity, $width, $height)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getPathForFilteredImage(File $entity, string $filterName): string
    {
        return $this->normalizePath(
            $this->fileUrlProvider->getFilteredImageUrl($entity, $filterName)
        );
    }

    private function normalizePath(string $path): string
    {
        return '/' . ltrim($path, '/');
    }
}
