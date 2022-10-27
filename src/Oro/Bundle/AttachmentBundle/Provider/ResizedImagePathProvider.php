<?php

namespace Oro\Bundle\AttachmentBundle\Provider;

use Oro\Bundle\AttachmentBundle\Entity\File;

/**
 * The file path provider that assumes that an image path is the same as absolute URL path.
 */
class ResizedImagePathProvider implements ResizedImagePathProviderInterface
{
    private FileUrlProviderInterface $fileUrlProvider;

    public function __construct(FileUrlProviderInterface $fileUrlProvider)
    {
        $this->fileUrlProvider = $fileUrlProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function getPathForResizedImage(File $entity, int $width, int $height, string $format = ''): string
    {
        return $this->normalizePath(
            $this->fileUrlProvider->getResizedImageUrl($entity, $width, $height, $format)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getPathForFilteredImage(File $entity, string $filterName, string $format = ''): string
    {
        return $this->normalizePath(
            $this->fileUrlProvider->getFilteredImageUrl($entity, $filterName, $format)
        );
    }

    private function normalizePath(string $path): string
    {
        return '/' . ltrim($path, '/');
    }
}
