<?php

namespace Oro\Bundle\AttachmentBundle\Manager;

use Liip\ImagineBundle\Binary\BinaryInterface;
use Oro\Bundle\AttachmentBundle\Entity\File;

/**
 * Interface for classes which manage full process of resizing and saving images.
 */
interface ImageResizeManagerInterface
{
    public function resize(File $file, int $width, int $height, bool $forceUpdate = false): ?BinaryInterface;

    public function applyFilter(File $file, string $filterName, bool $forceUpdate = false): ?BinaryInterface;
}
