<?php

namespace Oro\Bundle\AttachmentBundle\Provider;

use Liip\ImagineBundle\Binary\BinaryInterface;
use Oro\Bundle\AttachmentBundle\Entity\File;

/**
 * Represents a service to retrieve resized variants of images.
 */
interface ResizedImageProviderInterface
{
    /**
     * Applies LiipImagine filter to the given image.
     *
     * @param File $file
     * @param string $filterName
     * @param string $format
     *
     * @return BinaryInterface|null Binary of the resized according to the specified filter image or null on error
     */
    public function getFilteredImage(File $file, string $filterName, string $format = ''): ?BinaryInterface;

    /**
     * Applies LiipImagine filter to the given image.
     *
     * @param string $fileName
     * @param string $filterName
     * @param string $format
     *
     * @return BinaryInterface|null Binary of the resized according to the specified filter image or null on error
     */
    public function getFilteredImageByPath(string $fileName, string $filterName, string $format = ''): ?BinaryInterface;

    /**
     * Applies LiipImagine filter to the given image.
     *
     * @param string $content
     * @param string $filterName
     * @param string $format
     *
     * @return BinaryInterface|null Binary of the resized according to the specified filter image or null on error
     */
    public function getFilteredImageByContent(
        string $content,
        string $filterName,
        string $format = ''
    ): ?BinaryInterface;

    /**
     * Resizes the given image to the specified width and height.
     *
     * @param File $file
     * @param int $width
     * @param int $height
     * @param string $format
     *
     * @return BinaryInterface|null Binary of the resized image or null on error
     */
    public function getResizedImage(File $file, int $width, int $height, string $format = ''): ?BinaryInterface;

    /**
     * Resizes the given image to the specified width and height.
     *
     * @param string $fileName
     * @param int $width
     * @param int $height
     * @param string $format
     *
     * @return BinaryInterface|null Binary of the resized image or null on error
     */
    public function getResizedImageByPath(
        string $fileName,
        int $width,
        int $height,
        string $format = ''
    ): ?BinaryInterface;

    /**
     * Resizes the given image to the specified width and height.
     *
     * @param string $content
     * @param int $width
     * @param int $height
     * @param string $format
     *
     * @return BinaryInterface|null Binary of the resized image or null on error
     */
    public function getResizedImageByContent(
        string $content,
        int $width,
        int $height,
        string $format = ''
    ): ?BinaryInterface;
}
