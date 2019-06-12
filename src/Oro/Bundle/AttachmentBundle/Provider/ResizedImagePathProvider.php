<?php

namespace Oro\Bundle\AttachmentBundle\Provider;

use Oro\Bundle\AttachmentBundle\Entity\File;

/**
 * Default implementation of file path provider that assumes that image path is the same as absolute URL path.
 */
class ResizedImagePathProvider implements ResizedImagePathProviderInterface
{
    /** @var FileUrlProviderInterface */
    private $fileUrlProvider;

    /**
     * @param FileUrlProviderInterface $fileUrlProvider
     */
    public function __construct(FileUrlProviderInterface $fileUrlProvider)
    {
        $this->fileUrlProvider = $fileUrlProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function getPathForResizedImage(
        File $entity,
        int $width,
        int $height
    ): string {
        $imageUrl = $this->fileUrlProvider->getResizedImageUrl($entity, $width, $height);

        return '/' . ltrim($imageUrl, '/');
    }

    /**
     * {@inheritdoc}
     */
    public function getPathForFilteredImage(
        File $entity,
        string $filterName
    ): string {
        $imageUrl = $this->fileUrlProvider->getFilteredImageUrl($entity, $filterName);

        return '/' . ltrim($imageUrl, '/');
    }
}
