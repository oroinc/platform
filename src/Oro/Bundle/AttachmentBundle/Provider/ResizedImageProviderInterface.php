<?php

namespace Oro\Bundle\AttachmentBundle\Provider;

use Liip\ImagineBundle\Binary\BinaryInterface;
use Oro\Bundle\AttachmentBundle\Entity\File;

/**
 * Interface for classes which resize images and provide instance of BinaryInterface in return.
 */
interface ResizedImageProviderInterface
{
    /**
     * Applies LiipImagine filter to the given image.
     *
     * @param File|string $image File entity, path or raw file content.
     * @param string $filterName
     *
     * @return BinaryInterface|null Binary of the resized according to the specified filter image or null on error
     */
    public function getFilteredImage($image, string $filterName): ?BinaryInterface;

    /**
     * Resizes the given image to the specified width and height.
     *
     * @param File|string $image File entity, path or raw file content.
     * @param int $width
     * @param int $height
     *
     * @return BinaryInterface|null Binary of the resized image or null on error
     */
    public function getResizedImage($image, int $width, int $height): ?BinaryInterface;
}
