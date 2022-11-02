<?php

namespace Oro\Bundle\AttachmentBundle\Provider;

use Oro\Bundle\AttachmentBundle\Entity\File;

/**
 * Represents a service to get a filename for a specified File entity.
 * This interface is used to generate file links.
 */
interface FileNameProviderInterface
{
    /**
     * Gets a filename for the given File entity.
     */
    public function getFileName(File $file): string;

    /**
     * Gets a filtered image filename for the given File entity.
     */
    public function getFilteredImageName(File $file, string $filterName, string $format = ''): string;

    /**
     * Gets a resized image filename for the given File entity.
     */
    public function getResizedImageName(File $file, int $width, int $height, string $format = ''): string;
}
