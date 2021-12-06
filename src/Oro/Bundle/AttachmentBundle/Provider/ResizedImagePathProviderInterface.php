<?php

namespace Oro\Bundle\AttachmentBundle\Provider;

use Oro\Bundle\AttachmentBundle\Entity\File;

/**
 * An interface for classes which can provide a path by which a resized/filtered image is stored for a specific file.
 */
interface ResizedImagePathProviderInterface
{
    /**
     * Gets a path to a resized image for the given file.
     *
     * @param File $entity
     * @param int $width
     * @param int $height
     * @param string $format Adds extension to the filename. Leave empty to stay with default format.
     *
     * @return string
     */
    public function getPathForResizedImage(File $entity, int $width, int $height, string $format = ''): string;

    /**
     * Gets a path to an image with applied LiipImagine filter for the given file.
     *
     * @param File $entity
     * @param string $filterName
     * @param string $format Adds extension to the filename. Leave empty to stay with default format.
     *
     * @return string
     */
    public function getPathForFilteredImage(File $entity, string $filterName, string $format = ''): string;
}
