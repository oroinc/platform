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
     * @param bool $forceUpdate
     *
     * @return BinaryInterface|null
     */
    public function resize(File $file, int $width, int $height, bool $forceUpdate = false): ?BinaryInterface;

    /**
     * @param File $file
     * @param string $filterName
     * @param bool $forceUpdate
     *
     * @return BinaryInterface|null
     */
    public function applyFilter(File $file, string $filterName, bool $forceUpdate = false): ?BinaryInterface;
}
