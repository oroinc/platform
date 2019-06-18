<?php

namespace Oro\Bundle\AttachmentBundle\Provider;

use Oro\Bundle\AttachmentBundle\Entity\File;

/**
 * Interface for classes which can provide a path by which to store the resized image for a given file.
 */
interface ResizedImagePathProviderInterface
{
    /**
     * Get path for the resized image.
     *
     * @param File $entity
     * @param int $width
     * @param int $height
     *
     * @return string
     */
    public function getPathForResizedImage(
        File $entity,
        int $width,
        int $height
    ): string;

    /**
     * Get path for the image with applied liip imagine filter.
     *
     * @param File $entity
     * @param string $filterName
     *
     * @return string
     */
    public function getPathForFilteredImage(
        File $entity,
        string $filterName
    ): string;
}
