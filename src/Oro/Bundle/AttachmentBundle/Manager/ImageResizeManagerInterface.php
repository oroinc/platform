<?php

namespace Oro\Bundle\AttachmentBundle\Manager;

use Liip\ImagineBundle\Binary\BinaryInterface;
use Oro\Bundle\AttachmentBundle\Entity\File;

/**
 * Interface for classes which manage full process of resizing and saving images.
 */
interface ImageResizeManagerInterface
{
    /**
     * @param File $file
     * @param int $width
     * @param int $height
     * @param string $format Converts resized image to $format, e.g. webp.
     * @param bool $forceUpdate
     * @return BinaryInterface|null
     */
    public function resize(
        File $file,
        int $width,
        int $height,
        string $format = '',
        bool $forceUpdate = false
    ): ?BinaryInterface;

    /**
     * @param File $file
     * @param string $filterName
     * @param string $format Converts resized image to $format, e.g. webp. Takes precedence over filter config.
     * @param bool $forceUpdate
     * @return BinaryInterface|null
     */
    public function applyFilter(
        File $file,
        string $filterName,
        string $format = '',
        bool $forceUpdate = false
    ): ?BinaryInterface;
}
